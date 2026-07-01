<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Hold;
use App\Models\Loan;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Validate date range
        $startDateStr = $request->input('start_date');
        $endDateStr   = $request->input('end_date');

        try {
            $startDate = $startDateStr ? \Carbon\Carbon::parse($startDateStr)->startOfDay() : today()->subDays(13)->startOfDay();
            $endDate   = $endDateStr ? \Carbon\Carbon::parse($endDateStr)->endOfDay() : today()->endOfDay();
        } catch (\Exception $e) {
            $startDate = today()->subDays(13)->startOfDay();
            $endDate   = today()->endOfDay();
        }

        $props = [
            'personal' => $this->personalStats($user),
            'filters'  => [
                'start_date' => $startDate->toDateString(),
                'end_date'   => $endDate->toDateString(),
            ],
            'recent_books' => $this->recentBooks(),
        ];

        // Operational analytics only for staff who can see circulation data.
        if ($user->can('checkout_book') || $user->can('view_loans') || $user->can('manage_campus')) {
            $props['analytics'] = $this->analytics($startDate, $endDate);
            $props['recent_activity'] = $this->recentActivity();
        }

        return Inertia::render('Dashboard', $props);
    }

    /**
     * Per-user "my library" snapshot — shown to everyone.
     */
    protected function personalStats($user): array
    {
        $activeLoans = Loan::where('user_id', $user->id)->where('status', 'active');

        return [
            'active_loans' => (clone $activeLoans)->count(),
            'due_soon'     => (clone $activeLoans)
                ->whereDate('due_on', '>=', today())
                ->whereDate('due_on', '<=', today()->addDays((int) config('library.due_soon_days', 2)))
                ->count(),
            'overdue'      => (clone $activeLoans)->whereDate('due_on', '<', today())->count(),
            'holds'        => Hold::where('user_id', $user->id)->whereIn('status', ['waiting', 'ready'])->count(),
            'fines_due'    => round((float) Fine::where('user_id', $user->id)
                ->where('status', 'open')
                ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount')), 2),
        ];
    }

    /**
     * System-wide operational KPIs and chart data.
     */
    protected function analytics($startDate, $endDate): array
    {
        return [
            'kpis'              => $this->kpis(),
            'checkouts_trend'  => $this->checkoutsTrend($startDate, $endDate),
            'loans_by_status'  => $this->loansByStatus(),
            'top_titles'       => $this->topTitles(),
            'copies_by_campus' => $this->copiesByCampus(),
            'currency'         => config('library.currency_symbol'),
        ];
    }

    protected function kpis(): array
    {
        $activeLoans = Loan::where('status', 'active');

        return [
            'books'             => Book::count(),
            'copies'            => BookCopy::count(),
            'active_loans'      => (clone $activeLoans)->count(),
            'overdue'          => (clone $activeLoans)->whereDate('due_on', '<', today())->count(),
            'holds_waiting'     => Hold::whereIn('status', ['waiting', 'ready'])->count(),
            'transfers_pending' => Transfer::whereIn('status', ['requested', 'approved', 'dispatched'])->count(),
            'checkouts_today'   => Loan::whereDate('checked_out_at', today())->count(),
            'fines_outstanding' => round((float) Fine::where('status', 'open')
                ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount')), 2),
        ];
    }

    /**
     * Checkouts vs returns for custom date range.
     */
    protected function checkoutsTrend($startDate, $endDate): array
    {
        $checkouts = Loan::whereBetween('checked_out_at', [$startDate, $endDate])->get(['checked_out_at']);
        $returns   = Loan::whereBetween('returned_at', [$startDate, $endDate])->get(['returned_at']);

        $daysCount = (int) $startDate->diffInDays($endDate);
        if ($daysCount < 0) {
            $daysCount = 0;
        }

        return collect(range(0, $daysCount))->map(function ($i) use ($startDate, $checkouts, $returns) {
            $day = (clone $startDate)->addDays($i);

            return [
                'date'     => $day->format('M j'),
                'checkout' => $checkouts->filter(fn ($l) => $l->checked_out_at?->isSameDay($day))->count(),
                'return'   => $returns->filter(fn ($l) => $l->returned_at?->isSameDay($day))->count(),
            ];
        })->all();
    }

    protected function loansByStatus(): array
    {
        return Loan::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
    }

    protected function topTitles(): array
    {
        return Loan::query()
            ->join('book_copies', 'book_copies.id', '=', 'loans.book_copy_id')
            ->join('books', 'books.id', '=', 'book_copies.book_id')
            ->selectRaw('books.title as title, count(*) as loans')
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('loans')
            ->limit(6)
            ->get()
            ->all();
    }

    protected function copiesByCampus(): array
    {
        return BookCopy::query()
            ->join('shelf_boxes', 'shelf_boxes.id', '=', 'book_copies.current_shelf_box_id')
            ->join('rows', 'rows.id', '=', 'shelf_boxes.row_id')
            ->join('floors', 'floors.id', '=', 'rows.floor_id')
            ->join('campuses', 'campuses.id', '=', 'floors.campus_id')
            ->selectRaw('campuses.name as campus, count(*) as copies')
            ->groupBy('campuses.id', 'campuses.name')
            ->orderByDesc('copies')
            ->get()
            ->all();
    }

    protected function recentActivity(): array
    {
        return Activity::with('causer')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id'          => $activity->id,
                    'description' => $activity->description,
                    'log_name'    => $activity->log_name,
                    'event'       => $activity->event,
                    'causer_name' => $activity->causer ? $activity->causer->name : 'System',
                    'created_at'  => $activity->created_at->diffForHumans(),
                ];
            })
            ->all();
    }

    protected function recentBooks(): array
    {
        return Book::with('authors')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($book) {
                return [
                    'id'         => $book->id,
                    'title'      => $book->title,
                    'subtitle'   => $book->subtitle,
                    'cover_path' => $book->cover_path,
                    'cover_url'  => $book->cover_url,
                    'authors'    => $book->authors->pluck('name')->join(', '),
                ];
            })
            ->all();
    }
}
