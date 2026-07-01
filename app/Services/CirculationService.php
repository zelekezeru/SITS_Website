<?php

namespace App\Services;

use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Hold;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\HoldReady;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CirculationService
{
    public function checkout(BookCopy $copy, User $borrower, User $actor): Loan
    {
        return DB::transaction(function () use ($copy, $borrower, $actor) {
            $copy->refresh()->loadMissing('shelfBox.row.floor');

            $this->assertCheckoutAllowed($copy, $borrower, $actor);

            $policy   = LoanPolicy::for($borrower);
            $campusId = $copy->shelfBox->row->floor->campus_id;

            $loan = Loan::create([
                'book_copy_id'              => $copy->id,
                'user_id'                   => $borrower->id,
                'checked_out_by'            => $actor->id,
                'checked_out_at_campus_id'  => $campusId,
                'checked_out_at'            => now(),
                'due_on'                    => now()->addDays($policy->loanDays)->toDateString(),
            ]);

            $copy->update(['status' => \App\Enums\BookStatus::CHECKED_OUT]);

            // if this fulfilled a hold, mark it
            Hold::where('book_id', $copy->book_id)
                ->where('user_id', $borrower->id)
                ->whereIn('status', ['waiting','ready'])
                ->latest('placed_at')->first()
                ?->update(['status' => 'fulfilled']);

            return $loan;
        });
    }

    public function return(BookCopy $copy, User $actor, int $returnCampusId, ?string $conditionNote = null): Loan
    {
        return DB::transaction(function () use ($copy, $actor, $returnCampusId, $conditionNote) {
            $loan = Loan::where('book_copy_id', $copy->id)
                        ->where('status','active')
                        ->lockForUpdate()->firstOrFail();

            $wasOverdue = now()->startOfDay()->gt($loan->due_on->startOfDay());

            $loan->update([
                'returned_at'           => now(),
                'returned_to_campus_id' => $returnCampusId,
                'returned_by'           => $actor->id,
                'status'                => $wasOverdue ? 'overdue_returned' : 'returned',
            ]);

            // If the librarian flagged damage at return time, mark the copy
            if ($conditionNote) {
                $copy->update([
                    'status'    => \App\Enums\BookStatus::DAMAGED,
                    'condition' => $conditionNote,
                ]);
            } else {
                $copy->update(['status' => \App\Enums\BookStatus::AVAILABLE]);
            }

            if ($wasOverdue) {
                $this->assessOverdueFine($loan);
            }

            // Phase 7 hook: transfer back to home campus
            $homeCampusId = $copy->shelfBox?->row?->floor?->campus_id;
            if ($homeCampusId && $homeCampusId !== $returnCampusId && !$conditionNote) {
                app(\App\Services\TransferService::class)->request(
                    $copy,
                    $homeCampusId,
                    $actor,
                    "Returned to wrong campus; auto-routing home."
                );
            } elseif (!$conditionNote) {
                // Copy stays here and is now on the shelf — promote the next
                // waiting hold for this title and notify the patron.
                $this->promoteNextHold($copy, $returnCampusId);
            }

            return $loan;
        });
    }

    public function renew(Loan $loan, User $actor): Loan
    {
        $policy = LoanPolicy::for($loan->user);

        abort_if($loan->status !== 'active',                   422, 'Loan is not active.');
        abort_if($loan->renewal_count >= $policy->maxRenewals, 422, 'Renewal limit reached.');
        abort_if($this->hasWaitingHold($loan->copy->book_id),   422, 'Another user is waiting on this title.');

        $loan->update([
            'due_on'         => Carbon::parse($loan->due_on)->addDays($policy->loanDays)->toDateString(),
            'renewal_count'  => $loan->renewal_count + 1,
        ]);

        return $loan;
    }

    protected function assertCheckoutAllowed(BookCopy $copy, User $borrower, User $actor): void
    {
        $policy = LoanPolicy::for($borrower);

        abort_if($copy->status !== \App\Enums\BookStatus::AVAILABLE, 422,
            "Copy is {$copy->status->value} and cannot be checked out.");

        $copyCampusId = $copy->shelfBox?->row?->floor?->campus_id;
        
        // Let's assume User has current_campus_id (even if it's null, we handle it)
        abort_if(
            $copyCampusId !== ($borrower->current_campus_id ?? null)
                && ! $actor->can('override_campus_check'),
            422,
            'This copy is not at the borrower\'s current campus. Request a transfer first.'
        );

        $activeCount = Loan::where('user_id', $borrower->id)->where('status', 'active')->count();
        abort_if($activeCount >= $policy->maxConcurrent, 422,
            "Borrower already has {$activeCount} active loans (limit {$policy->maxConcurrent}).");

        $hasOpenFines = Fine::where('user_id', $borrower->id)
            ->where('status', 'open')->where('amount', '>', DB::raw('paid_amount'))->exists();
            
        abort_if($hasOpenFines && ! $actor->can('override_campus_check'), 422,
            'Borrower has unpaid fines.');
    }

    protected function hasWaitingHold(int $bookId): bool
    {
        return Hold::where('book_id', $bookId)->whereIn('status', ['waiting', 'ready'])->exists();
    }

    /**
     * When a copy becomes available, promote the oldest waiting hold for the
     * same title (at this campus) to "ready" and notify the patron.
     */
    protected function promoteNextHold(BookCopy $copy, int $campusId): void
    {
        $hold = Hold::where('book_id', $copy->book_id)
            ->where('status', 'waiting')
            ->where(fn ($q) => $q->where('campus_id', $campusId)->orWhereNull('campus_id'))
            ->orderBy('placed_at')
            ->first();

        if (! $hold) {
            return;
        }

        $hold->update([
            'status'       => 'ready',
            'available_at' => now(),
            'expires_at'   => now()->addDays(config('library.hold_shelf_days', 3)),
        ]);

        $hold->loadMissing('book', 'campus', 'user');
        $hold->user?->notify(new HoldReady($hold));
    }

    protected function assessOverdueFine(Loan $loan): void
    {
        $daysLate = $loan->due_on->startOfDay()->diffInDays(today()->startOfDay());
        if ($daysLate > 0) {
            $finePerDay = (float) config('library.fine_per_day', 5);
            $fineCap    = (float) config('library.fine_cap', 0);

            $calculatedAmount = $daysLate * $finePerDay;

            // Enforce fine cap if configured
            if ($fineCap > 0) {
                $calculatedAmount = min($calculatedAmount, $fineCap);
            }

            $fine = Fine::firstOrCreate(
                ['loan_id' => $loan->id, 'reason' => 'overdue'],
                ['user_id' => $loan->user_id, 'amount' => 0]
            );
            $fine->update(['amount' => $calculatedAmount]);
        }
    }
}
