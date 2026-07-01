<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Fine;
use App\Models\Hold;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class PatronController extends Controller
{
    public function show(Request $request, User $user)
    {
        abort_unless(
            $request->user()->can('manage_users') || $request->user()->can('view_loans'),
            403
        );

        $user->load('roles', 'currentCampus');

        // Loan history (most recent first)
        $loans = Loan::where('user_id', $user->id)
            ->with(['copy.book', 'checkedOutCampus'])
            ->orderByDesc('checked_out_at')
            ->paginate(15, ['*'], 'loans_page')
            ->through(fn ($loan) => [
                'id'             => $loan->id,
                'book_title'     => $loan->copy?->book?->title ?? 'Unknown',
                'copy_barcode'   => $loan->copy?->barcode,
                'campus'         => $loan->checkedOutCampus?->name,
                'checked_out_at' => $loan->checked_out_at?->format('M j, Y'),
                'due_on'         => $loan->due_on?->format('M j, Y'),
                'returned_at'    => $loan->returned_at?->format('M j, Y'),
                'status'         => $loan->status,
                'is_overdue'     => $loan->is_overdue,
                'days_overdue'   => $loan->days_overdue,
                'renewal_count'  => $loan->renewal_count,
            ]);

        // Active holds
        $holds = Hold::where('user_id', $user->id)
            ->with(['book', 'campus'])
            ->orderByDesc('placed_at')
            ->get()
            ->map(fn ($hold) => [
                'id'           => $hold->id,
                'book_title'   => $hold->book?->title,
                'campus'       => $hold->campus?->name,
                'status'       => $hold->status,
                'placed_at'    => $hold->placed_at?->format('M j, Y'),
                'available_at' => $hold->available_at?->format('M j, Y'),
                'expires_at'   => $hold->expires_at?->format('M j, Y'),
            ]);

        // Fines & payments
        $fines = Fine::where('user_id', $user->id)
            ->with('loan.copy.book')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($fine) => [
                'id'          => $fine->id,
                'book_title'  => $fine->loan?->copy?->book?->title,
                'reason'      => $fine->reason,
                'amount'      => $fine->amount,
                'paid_amount' => $fine->paid_amount,
                'balance'     => $fine->balance,
                'status'      => $fine->status,
                'created_at'  => $fine->created_at?->format('M j, Y'),
            ]);

        // Recent activity log
        $activities = Activity::where('causer_id', $user->id)
            ->where('causer_type', User::class)
            ->orderByDesc('created_at')
            ->take(30)
            ->get()
            ->map(fn ($activity) => [
                'id'          => $activity->id,
                'description' => $activity->description,
                'subject'     => $activity->subject_type ? class_basename($activity->subject_type) : null,
                'event'       => $activity->event,
                'created_at'  => $activity->created_at?->diffForHumans(),
            ]);

        // Summary stats
        $summary = [
            'total_loans'      => Loan::where('user_id', $user->id)->count(),
            'active_loans'     => Loan::where('user_id', $user->id)->where('status', 'active')->count(),
            'overdue_loans'    => Loan::where('user_id', $user->id)->where('status', 'active')
                ->whereDate('due_on', '<', today())->count(),
            'total_fines'      => round((float) Fine::where('user_id', $user->id)->sum('amount'), 2),
            'outstanding_fines' => round((float) Fine::where('user_id', $user->id)
                ->where('status', 'open')
                ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount')), 2),
            'active_holds'     => Hold::where('user_id', $user->id)
                ->whereIn('status', ['waiting', 'ready'])->count(),
        ];

        return Inertia::render('Library/Users/Show', [
            'patron'     => array_merge($user->only('id', 'name', 'email', 'created_at'), [
                'role'         => $user->primaryRole()?->label() ?? 'No role',
                'role_value'   => $user->primaryRole()?->value,
                'campus'       => $user->currentCampus?->name ?? 'Unassigned',
                'member_since' => $user->created_at?->format('M j, Y'),
            ]),
            'summary'    => $summary,
            'loans'      => $loans,
            'holds'      => $holds,
            'fines'      => $fines,
            'activities' => $activities,
            'currency'   => config('library.currency_symbol'),
        ]);
    }
}
