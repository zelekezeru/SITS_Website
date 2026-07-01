<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Campus;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Transfer;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const TYPES = ['circulation', 'collection', 'fines', 'patrons', 'campus', 'overdue'];

    /** Report types selectable in the UI, with display metadata. */
    public static function available(): array
    {
        return [
            ['key' => 'circulation', 'label' => 'Circulation Summary',   'icon' => 'chart-bar'],
            ['key' => 'collection',  'label' => 'Collection Statistics', 'icon' => 'library'],
            ['key' => 'fines',       'label' => 'Fines & Revenue',       'icon' => 'currency-dollar'],
            ['key' => 'patrons',     'label' => 'Patron Activity',       'icon' => 'users'],
            ['key' => 'campus',      'label' => 'Campus Comparison',     'icon' => 'building-office'],
            ['key' => 'overdue',     'label' => 'Overdue Loans',         'icon' => 'clock'],
        ];
    }

    public function build(string $report, CarbonInterface $from, CarbonInterface $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        return match ($report) {
            'collection' => $this->collectionReport(),
            'fines'      => $this->finesReport($from, $to),
            'patrons'    => $this->patronsReport($from, $to),
            'campus'     => $this->campusReport($from, $to),
            'overdue'    => $this->overdueReport(),
            default      => $this->circulationReport($from, $to),
        };
    }

    /**
     * Flatten a report into tabular sections for export.
     *
     * @return array<int, array{title: string, headers: array, rows: array}>
     */
    public function sections(string $report, CarbonInterface $from, CarbonInterface $to): array
    {
        $data = $this->build($report, $from, $to);

        $sections = [];

        if (isset($data['summary'])) {
            $sections[] = [
                'title'   => 'Summary',
                'headers' => ['Metric', 'Value'],
                'rows'    => collect($data['summary'])
                    ->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', $key)), $value])
                    ->values()->all(),
            ];
        }

        switch ($report) {
            case 'circulation':
                $sections[] = [
                    'title'   => 'Daily Trend',
                    'headers' => ['Date', 'Checkouts', 'Returns'],
                    'rows'    => collect($data['trend'])->map(fn ($d) => [$d['date'], $d['checkouts'], $d['returns']])->all(),
                ];
                $sections[] = [
                    'title'   => 'Top Borrowed Titles',
                    'headers' => ['Title', 'Loans'],
                    'rows'    => collect($data['top_books'])->map(fn ($b) => [$b->title, $b->total])->all(),
                ];
                $sections[] = [
                    'title'   => 'Loans by Status',
                    'headers' => ['Status', 'Count'],
                    'rows'    => collect($data['by_status'])->map(fn ($count, $status) => [$status, $count])->values()->all(),
                ];
                break;

            case 'collection':
                $sections[] = [
                    'title'   => 'Copies by Campus',
                    'headers' => ['Campus', 'Copies'],
                    'rows'    => collect($data['by_campus'])->map(fn ($c) => [$c->campus, $c->copies])->all(),
                ];
                $sections[] = [
                    'title'   => 'Titles by Category',
                    'headers' => ['Category', 'Titles'],
                    'rows'    => collect($data['by_category'])->map(fn ($c) => [$c->category, $c->titles])->all(),
                ];
                $sections[] = [
                    'title'   => 'Titles by Publication Year',
                    'headers' => ['Year', 'Titles'],
                    'rows'    => collect($data['by_year'])->map(fn ($y) => [$y->published_year, $y->count])->all(),
                ];
                break;

            case 'fines':
                $sections[] = [
                    'title'   => 'Daily Trend',
                    'headers' => ['Date', 'Assessed', 'Collected'],
                    'rows'    => collect($data['trend'])->map(fn ($d) => [$d['date'], $d['assessed'], $d['collected']])->all(),
                ];
                break;

            case 'patrons':
                $sections[] = [
                    'title'   => 'Top Borrowers',
                    'headers' => ['Patron', 'Loans'],
                    'rows'    => collect($data['top_borrowers'])->map(fn ($b) => [$b->name, $b->total_loans])->all(),
                ];
                $sections[] = [
                    'title'   => 'Top Outstanding Fines',
                    'headers' => ['Patron', 'Balance'],
                    'rows'    => collect($data['top_debtors'])->map(fn ($d) => [$d->name, round((float) $d->balance, 2)])->all(),
                ];
                $sections[] = [
                    'title'   => 'Users by Role',
                    'headers' => ['Role', 'Count'],
                    'rows'    => collect($data['by_role'])->map(fn ($r) => [$r->role, $r->count])->all(),
                ];
                break;

            case 'campus':
                $sections[] = [
                    'title'   => 'Campus Comparison',
                    'headers' => ['Campus', 'Code', 'Copies', 'Checkouts', 'Returns', 'Transfers In', 'Transfers Out', 'Utilization %'],
                    'rows'    => collect($data['comparison'])->map(fn ($c) => [
                        $c['campus'], $c['code'], $c['copies'], $c['checkouts'], $c['returns'],
                        $c['transfers_in'], $c['transfers_out'], $c['utilization'],
                    ])->all(),
                ];
                break;

            case 'overdue':
                $sections[] = [
                    'title'   => 'Overdue Loans',
                    'headers' => ['Patron', 'Email', 'Title', 'Barcode', 'Campus', 'Due Date', 'Days Overdue', 'Renewals', 'Outstanding Fine'],
                    'rows'    => collect($data['loans'])->map(fn ($l) => [
                        $l['patron'], $l['email'], $l['title'], $l['barcode'], $l['campus'],
                        $l['due_on'], $l['days_overdue'], $l['renewals'], $l['fine_balance'],
                    ])->all(),
                ];
                break;
        }

        return array_values(array_filter($sections, fn ($s) => $s['rows'] !== []));
    }

    protected function circulationReport(CarbonInterface $from, CarbonInterface $to): array
    {
        $checkoutsByDay = Loan::whereBetween('checked_out_at', [$from, $to])
            ->selectRaw('DATE(checked_out_at) as day, count(*) as total')
            ->groupBy('day')->pluck('total', 'day');

        $returnsByDay = Loan::whereBetween('returned_at', [$from, $to])
            ->selectRaw('DATE(returned_at) as day, count(*) as total')
            ->groupBy('day')->pluck('total', 'day');

        $trend = collect();
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $trend->push([
                'date'      => $day->format('M j'),
                'checkouts' => (int) ($checkoutsByDay[$key] ?? 0),
                'returns'   => (int) ($returnsByDay[$key] ?? 0),
            ]);
        }

        $topBooks = Loan::whereBetween('checked_out_at', [$from, $to])
            ->join('book_copies', 'book_copies.id', '=', 'loans.book_copy_id')
            ->join('books', 'books.id', '=', 'book_copies.book_id')
            ->selectRaw('books.title, books.id, count(*) as total')
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byStatus = Loan::whereBetween('checked_out_at', [$from, $to])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'summary' => [
                'total_checkouts' => $checkoutsByDay->sum(),
                'total_returns'   => $returnsByDay->sum(),
                'renewals'        => Loan::whereBetween('checked_out_at', [$from, $to])
                    ->where('renewal_count', '>', 0)->count(),
                'overdue_active'  => Loan::where('status', 'active')
                    ->whereDate('due_on', '<', today())->count(),
                'avg_loan_days'   => $this->averageLoanDays($from, $to),
            ],
            'trend'     => $trend,
            'top_books' => $topBooks,
            'by_status' => $byStatus,
        ];
    }

    /** Average days between checkout and return, portable across DB drivers. */
    protected function averageLoanDays(CarbonInterface $from, CarbonInterface $to): float
    {
        $expression = match (DB::connection()->getDriverName()) {
            'sqlite'          => 'JULIANDAY(returned_at) - JULIANDAY(checked_out_at)',
            'mysql', 'mariadb' => 'DATEDIFF(returned_at, checked_out_at)',
            'pgsql'           => 'EXTRACT(EPOCH FROM (returned_at - checked_out_at)) / 86400.0',
            default           => null,
        };

        if ($expression === null) {
            return 0.0;
        }

        return round((float) Loan::whereBetween('checked_out_at', [$from, $to])
            ->whereNotNull('returned_at')
            ->avg(DB::raw($expression)), 1);
    }

    protected function collectionReport(): array
    {
        $byStatus = BookCopy::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byCampus = BookCopy::query()
            ->join('shelf_boxes', 'shelf_boxes.id', '=', 'book_copies.current_shelf_box_id')
            ->join('rows', 'rows.id', '=', 'shelf_boxes.row_id')
            ->join('floors', 'floors.id', '=', 'rows.floor_id')
            ->join('campuses', 'campuses.id', '=', 'floors.campus_id')
            ->selectRaw('campuses.name as campus, count(*) as copies')
            ->groupBy('campuses.id', 'campuses.name')
            ->orderByDesc('copies')
            ->get();

        $byCategory = Book::join('book_category', 'books.id', '=', 'book_category.book_id')
            ->join('categories', 'categories.id', '=', 'book_category.category_id')
            ->selectRaw('categories.name as category, count(DISTINCT books.id) as titles')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('titles')
            ->get();

        $byYear = Book::selectRaw('published_year, count(*) as count')
            ->whereNotNull('published_year')
            ->groupBy('published_year')
            ->orderBy('published_year')
            ->get();

        return [
            'summary' => [
                'total_titles' => Book::count(),
                'total_copies' => BookCopy::count(),
                'available'    => (int) ($byStatus['available'] ?? 0),
                'checked_out'  => (int) ($byStatus['checked_out'] ?? 0),
                'lost'         => (int) ($byStatus['lost'] ?? 0),
                'damaged'      => (int) ($byStatus['damaged'] ?? 0),
            ],
            'by_status'   => $byStatus,
            'by_campus'   => $byCampus,
            'by_category' => $byCategory,
            'by_year'     => $byYear,
        ];
    }

    protected function finesReport(CarbonInterface $from, CarbonInterface $to): array
    {
        $byDay = Fine::whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as assessed, SUM(paid_amount) as collected')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $trend = collect();
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $entry = $byDay[$day->toDateString()] ?? null;
            $trend->push([
                'date'      => $day->format('M j'),
                'assessed'  => round((float) ($entry->assessed ?? 0), 2),
                'collected' => round((float) ($entry->collected ?? 0), 2),
            ]);
        }

        $fines = Fine::whereBetween('created_at', [$from, $to]);

        return [
            'summary' => [
                'total_assessed'  => round((float) (clone $fines)->sum('amount'), 2),
                'total_collected' => round((float) (clone $fines)->sum('paid_amount'), 2),
                'outstanding'     => round((float) Fine::where('status', 'open')
                    ->sum(DB::raw('amount - paid_amount')), 2),
                'fines_count'     => (clone $fines)->count(),
                'waivers_count'   => (clone $fines)->where('status', 'waived')->count(),
            ],
            'trend' => $trend,
        ];
    }

    protected function patronsReport(CarbonInterface $from, CarbonInterface $to): array
    {
        $topBorrowers = Loan::whereBetween('checked_out_at', [$from, $to])
            ->join('users', 'users.id', '=', 'loans.user_id')
            ->selectRaw('users.name, users.id, count(*) as total_loans')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_loans')
            ->limit(15)
            ->get();

        $topDebtors = Fine::where('status', 'open')
            ->join('users', 'users.id', '=', 'fines.user_id')
            ->selectRaw('users.name, users.id, SUM(fines.amount - fines.paid_amount) as balance')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('balance')
            ->limit(10)
            ->get();

        $byRole = User::join('model_has_roles', function ($j) {
                $j->on('model_has_roles.model_id', '=', 'users.id')
                  ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->selectRaw('roles.name as role, count(*) as count')
            ->groupBy('roles.id', 'roles.name')
            ->get();

        return [
            'summary' => [
                'total_users'       => User::count(),
                'active_borrowers'  => Loan::where('status', 'active')->distinct('user_id')->count('user_id'),
                'new_registrations' => User::whereBetween('created_at', [$from, $to])->count(),
            ],
            'top_borrowers' => $topBorrowers,
            'top_debtors'   => $topDebtors,
            'by_role'       => $byRole,
        ];
    }

    protected function campusReport(CarbonInterface $from, CarbonInterface $to): array
    {
        $campuses = Campus::where('is_active', true)->get();

        $comparison = $campuses->map(function ($campus) use ($from, $to) {
            $campusId = $campus->id;

            $copies = BookCopy::atCampus($campusId)->count();
            $checkouts = Loan::where('checked_out_at_campus_id', $campusId)
                ->whereBetween('checked_out_at', [$from, $to])->count();
            $returns = Loan::where('returned_to_campus_id', $campusId)
                ->whereBetween('returned_at', [$from, $to])->count();
            $transfersIn = Transfer::where('to_campus_id', $campusId)
                ->where('status', 'received')
                ->whereBetween('received_at', [$from, $to])->count();
            $transfersOut = Transfer::where('from_campus_id', $campusId)
                ->whereBetween('requested_at', [$from, $to])->count();

            return [
                'campus'        => $campus->name,
                'code'          => $campus->code,
                'copies'        => $copies,
                'checkouts'     => $checkouts,
                'returns'       => $returns,
                'transfers_in'  => $transfersIn,
                'transfers_out' => $transfersOut,
                'utilization'   => $copies > 0
                    ? round(($checkouts / $copies) * 100, 1) : 0,
            ];
        });

        return [
            'comparison' => $comparison,
        ];
    }

    /** All currently overdue active loans, oldest first, with fine balances. */
    protected function overdueReport(): array
    {
        $loans = Loan::where('status', 'active')
            ->whereDate('due_on', '<', today())
            ->with([
                'user:id,name,email',
                'copy:id,book_id,barcode',
                'copy.book:id,title',
                'checkedOutCampus:id,name',
                'fines' => fn ($q) => $q->where('reason', 'overdue')->where('status', 'open'),
            ])
            ->orderBy('due_on')
            ->get();

        $rows = $loans->map(fn ($loan) => [
            'id'           => $loan->id,
            'patron'       => $loan->user?->name ?? 'Unknown',
            'email'        => $loan->user?->email,
            'user_id'      => $loan->user_id,
            'title'        => $loan->copy?->book?->title ?? 'Unknown',
            'barcode'      => $loan->copy?->barcode,
            'campus'       => $loan->checkedOutCampus?->name,
            'due_on'       => $loan->due_on?->format('M j, Y'),
            'days_overdue' => $loan->days_overdue,
            'renewals'     => $loan->renewal_count,
            'fine_balance' => round((float) $loan->fines->sum(fn ($f) => $f->amount - $f->paid_amount), 2),
        ]);

        return [
            'summary' => [
                'overdue_loans'     => $rows->count(),
                'patrons_affected'  => $rows->pluck('user_id')->unique()->count(),
                'total_fines_due'   => round((float) $rows->sum('fine_balance'), 2),
                'max_days_overdue'  => (int) $rows->max('days_overdue'),
            ],
            'loans' => $rows->values(),
        ];
    }
}
