<?php

namespace App\Services;

use App\Enums\BookStatus;
use App\Models\BookCopy;
use App\Models\Hold;
use App\Models\Loan;
use App\Models\PlacementLog;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function request(BookCopy $copy, int $toCampusId, User $actor, ?string $reason = null,
                            ?Hold $forHold = null, ?Loan $forLoan = null): Transfer
    {
        return DB::transaction(function () use ($copy, $toCampusId, $actor, $reason, $forHold, $forLoan) {
            $copy->refresh()->loadMissing('shelfBox.row.floor');

            abort_if($copy->status !== BookStatus::AVAILABLE, 422,
                "Copy is {$copy->status->value} — only available copies can be transferred.");

            $fromCampusId = $copy->shelfBox?->row?->floor?->campus_id;
            abort_if(!$fromCampusId,            422, 'Copy has no current campus.');
            abort_if($fromCampusId === $toCampusId, 422, 'Source and destination are the same.');

            // prevent overlapping transfers on the same copy
            abort_if(Transfer::where('book_copy_id', $copy->id)->open()->exists(), 422,
                'Copy already has an open transfer.');

            $transfer = Transfer::create([
                'book_copy_id'   => $copy->id,
                'from_campus_id' => $fromCampusId,
                'to_campus_id'   => $toCampusId,
                'requested_by'   => $actor->id,
                'reason'         => $reason,
                'reason_hold_id' => $forHold?->id,
                'reason_loan_id' => $forLoan?->id,
                'requested_at'   => now(),
                'status'         => 'requested',
            ]);

            $copy->update(['status' => BookStatus::PENDING_TRANSFER]);
            return $transfer;
        });
    }

    public function approve(Transfer $t, User $actor): Transfer
    {
        return DB::transaction(function () use ($t, $actor) {
            abort_if($t->status !== 'requested', 422, "Transfer is {$t->status}.");
            $t->update(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()]);
            return $t;
        });
    }

    public function reject(Transfer $t, User $actor, string $note): Transfer
    {
        return DB::transaction(function () use ($t, $actor, $note) {
            abort_if($t->status !== 'requested', 422, "Transfer is {$t->status}.");
            $t->update([
                'status' => 'rejected', 'approved_by' => $actor->id,
                'approved_at' => now(), 'rejection_note' => $note,
            ]);
            $t->copy->update(['status' => BookStatus::AVAILABLE]);   // returns to shelf at origin
            return $t;
        });
    }

    public function dispatch(Transfer $t, User $actor, ?string $courierRef = null): Transfer
    {
        return DB::transaction(function () use ($t, $actor, $courierRef) {
            abort_if($t->status !== 'approved', 422, "Transfer must be approved first.");
            
            $fromShelfBoxId = $t->copy->current_shelf_box_id;
            
            $t->update([
                'status'         => 'in_transit',
                'dispatched_by'  => $actor->id,
                'dispatched_at'  => now(),
                'courier_ref'    => $courierRef,
            ]);
            
            // copy leaves its shelf
            PlacementLog::create([
                'book_copy_id'      => $t->book_copy_id,
                'from_shelf_box_id' => $fromShelfBoxId,
                'to_shelf_box_id'   => null,
                'user_id'           => $actor->id,
                'reason'            => 'transfer',
                'note'              => "Transfer #{$t->id} → {$t->toCampus->code}",
            ]);
            
            $t->copy->update([
                'status' => BookStatus::IN_TRANSIT,
                'current_shelf_box_id' => null,
            ]);
            
            return $t;
        });
    }

    public function receive(Transfer $t, User $actor): Transfer
    {
        return DB::transaction(function () use ($t, $actor) {
            abort_if($t->status !== 'in_transit', 422, "Transfer is {$t->status}.");
            $t->update(['status' => 'received', 'received_by' => $actor->id, 'received_at' => now()]);
            $t->copy->update(['status' => BookStatus::AVAILABLE]);
            return $t;
        });
    }

    public function returnToOrigin(Transfer $t, User $actor, string $note): Transfer 
    {
        return DB::transaction(function () use ($t, $actor, $note) {
            abort_if($t->status !== 'in_transit', 422, "Transfer is {$t->status}.");
            $t->update(['status' => 'returned_to_origin', 'received_by' => $actor->id, 'received_at' => now(), 'rejection_note' => $note]);
            $t->copy->update(['status' => BookStatus::AVAILABLE]);
            return $t;
        });
    }

    public function markLost(Transfer $t, User $actor, string $note): Transfer
    {
        return DB::transaction(function () use ($t, $actor, $note) {
            abort_if($t->status !== 'in_transit', 422, "Transfer is {$t->status}.");
            $t->update(['status' => 'lost', 'received_by' => $actor->id, 'received_at' => now(), 'rejection_note' => $note]);
            $t->copy->update(['status' => BookStatus::LOST]);
            return $t;
        });
    }
}
