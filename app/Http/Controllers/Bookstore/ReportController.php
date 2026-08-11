<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\BookPaymentStatus;
use App\Enums\BookRequestStatus;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\BookRequest;
use App\Models\BookTitle;
use App\Models\Program;
use App\Models\StoreRoom;
use App\Models\StudyMode;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every bookstore report, behind one screen with one report picker.
 *
 * Each report is a method returning a Collection of flat rows, so the same
 * builder feeds both the on-screen table and the CSV export — the numbers can
 * never disagree between the two.
 */
class ReportController extends Controller
{
    private const REPORTS = [
        'stock_on_hand'       => 'Stock on hand',
        'movement_summary'    => 'Movement summary',
        'distribution'        => 'Distribution by destination',
        'outstanding_returns' => 'Outstanding returns',
        'request_pipeline'    => 'Request pipeline ageing',
        'payments'            => 'Payments and CRV traceability',
        'audit_variance'      => 'Audit variance',
        'reprint_forecast'    => 'Reprint forecast',
    ];

    public function index(Request $request): Response
    {
        $report  = $request->input('report', 'stock_on_hand');
        $filters = $request->only(['from', 'to', 'store_room_id', 'program_id', 'study_mode_id']);

        abort_unless(array_key_exists($report, self::REPORTS), 404);

        return Inertia::render('Bookstore/Reports/Index', [
            'report'  => $report,
            'reports' => self::REPORTS,
            'filters' => $filters,
            'rows'    => $this->build($report, $filters)->values(),
            'options' => [
                'stores'     => StoreRoom::active()->orderBy('name')->get(['id', 'name']),
                'programs'   => Program::orderBy('title')->get(['id', 'title']),
                'studyModes' => StudyMode::active()->ordered()->get(['id', 'name']),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $report  = $request->input('report', 'stock_on_hand');
        $filters = $request->only(['from', 'to', 'store_room_id', 'program_id', 'study_mode_id']);

        abort_unless(array_key_exists($report, self::REPORTS), 404);

        $rows     = $this->build($report, $filters);
        $filename = 'bookstore-'.str_replace('_', '-', $report).'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            if ($rows->isNotEmpty()) {
                fputcsv($handle, array_keys((array) $rows->first()));

                foreach ($rows as $row) {
                    fputcsv($handle, array_values((array) $row));
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function build(string $report, array $filters): Collection
    {
        return match ($report) {
            'stock_on_hand'       => $this->stockOnHand($filters),
            'movement_summary'    => $this->movementSummary($filters),
            'distribution'        => $this->distribution($filters),
            'outstanding_returns' => $this->outstandingReturns(),
            'request_pipeline'    => $this->requestPipeline(),
            'payments'            => $this->payments($filters),
            'audit_variance'      => $this->auditVariance(),
            'reprint_forecast'    => $this->reprintForecast(),
            default               => collect(),
        };
    }

    /** Where every copy is, with valuation at cost and at selling price. */
    protected function stockOnHand(array $filters): Collection
    {
        return DB::table('book_stocks')
            ->join('book_titles', 'book_titles.id', '=', 'book_stocks.book_title_id')
            ->join('shelf_sections', 'shelf_sections.id', '=', 'book_stocks.shelf_section_id')
            ->join('shelves', 'shelves.id', '=', 'shelf_sections.shelf_id')
            ->join('store_rooms', 'store_rooms.id', '=', 'shelves.store_room_id')
            ->leftJoin('programs', 'programs.id', '=', 'book_titles.program_id')
            ->leftJoin('study_modes', 'study_modes.id', '=', 'book_titles.study_mode_id')
            ->when($filters['store_room_id'] ?? null, fn ($q, $id) => $q->where('store_rooms.id', $id))
            ->when($filters['program_id'] ?? null, fn ($q, $id) => $q->where('book_titles.program_id', $id))
            ->when($filters['study_mode_id'] ?? null, fn ($q, $id) => $q->where('book_titles.study_mode_id', $id))
            ->orderBy('book_titles.title')
            ->get([
                'book_titles.code as code',
                'book_titles.title as title',
                DB::raw('coalesce(programs.title, \'\') as program'),
                DB::raw('coalesce(study_modes.name, \'\') as study_mode'),
                'book_titles.language as language',
                'store_rooms.name as store',
                'shelves.code as shelf',
                'shelf_sections.code as section',
                'book_stocks.quantity as on_hand',
                'book_stocks.reserved_quantity as reserved',
                DB::raw('(book_stocks.quantity - book_stocks.reserved_quantity) as available'),
                DB::raw('round(book_stocks.quantity * coalesce(book_titles.unit_cost, 0), 2) as value_at_cost'),
                DB::raw('round(book_stocks.quantity * book_titles.unit_price, 2) as value_at_price'),
            ]);
    }

    /** Received / issued / returned / adjusted per title for a date range. */
    protected function movementSummary(array $filters): Collection
    {
        // One conditional sum per movement type, so a title appears once with a
        // column per direction rather than one row per type.
        $sum = fn (StockMovementType $type, string $alias) => DB::raw(
            "coalesce(sum(case when stock_movements.type = '{$type->value}' then stock_movements.quantity else 0 end), 0) as {$alias}"
        );

        return DB::table('stock_movements')
            ->join('book_titles', 'book_titles.id', '=', 'stock_movements.book_title_id')
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('stock_movements.occurred_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('stock_movements.occurred_at', '<=', $to))
            ->groupBy('book_titles.id', 'book_titles.code', 'book_titles.title')
            ->orderBy('book_titles.title')
            ->get([
                'book_titles.code as code',
                'book_titles.title as title',
                $sum(StockMovementType::RECEIPT, 'received'),
                $sum(StockMovementType::ISSUE, 'issued'),
                $sum(StockMovementType::RETURN_IN, 'returned'),
                $sum(StockMovementType::DAMAGE, 'damaged'),
                $sum(StockMovementType::LOSS, 'lost'),
            ]);
    }

    /** What each centre/campus has been issued, and what it cost them. */
    protected function distribution(array $filters): Collection
    {
        return DB::table('book_dispatch_items')
            ->join('book_dispatches', 'book_dispatches.id', '=', 'book_dispatch_items.book_dispatch_id')
            ->join('book_requests', 'book_requests.id', '=', 'book_dispatches.book_request_id')
            ->leftJoin('centers', 'centers.id', '=', 'book_requests.center_id')
            ->leftJoin('campuses', 'campuses.id', '=', 'book_requests.campus_id')
            ->join('book_titles', 'book_titles.id', '=', 'book_dispatch_items.book_title_id')
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('book_dispatches.dispatched_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('book_dispatches.dispatched_at', '<=', $to))
            ->groupBy('destination', 'book_titles.code', 'book_titles.title')
            ->orderBy('destination')
            ->get([
                DB::raw('coalesce(centers.name, campuses.name, \'—\') as destination'),
                'book_titles.code as code',
                'book_titles.title as title',
                DB::raw('sum(book_dispatch_items.quantity) as issued'),
                DB::raw('round(sum(book_dispatch_items.line_total), 2) as value'),
            ]);
    }

    /** Issued minus returned per centre — the paper form's right-hand column. */
    protected function outstandingReturns(): Collection
    {
        $issued = DB::table('book_dispatch_items')
            ->join('book_dispatches', 'book_dispatches.id', '=', 'book_dispatch_items.book_dispatch_id')
            ->join('book_requests', 'book_requests.id', '=', 'book_dispatches.book_request_id')
            ->whereNotNull('book_requests.center_id')
            ->groupBy('book_requests.center_id', 'book_dispatch_items.book_title_id')
            ->select(
                'book_requests.center_id',
                'book_dispatch_items.book_title_id',
                DB::raw('sum(book_dispatch_items.quantity) as issued')
            );

        $returned = DB::table('book_return_items')
            ->join('book_returns', 'book_returns.id', '=', 'book_return_items.book_return_id')
            ->whereNotNull('book_returns.center_id')
            ->groupBy('book_returns.center_id', 'book_return_items.book_title_id')
            ->select(
                'book_returns.center_id',
                'book_return_items.book_title_id',
                DB::raw('sum(book_return_items.quantity_returned) as returned')
            );

        return DB::query()
            ->fromSub($issued, 'i')
            ->leftJoinSub($returned, 'r', function ($join) {
                $join->on('r.center_id', '=', 'i.center_id')
                    ->on('r.book_title_id', '=', 'i.book_title_id');
            })
            ->join('centers', 'centers.id', '=', 'i.center_id')
            ->join('book_titles', 'book_titles.id', '=', 'i.book_title_id')
            ->havingRaw('outstanding > 0')
            ->orderBy('centers.name')
            ->get([
                'centers.name as center',
                'centers.coordinator_name as coordinator',
                'centers.coordinator_phone as phone',
                'book_titles.code as code',
                'book_titles.title as title',
                DB::raw('i.issued as issued'),
                DB::raw('coalesce(r.returned, 0) as returned'),
                DB::raw('(i.issued - coalesce(r.returned, 0)) as outstanding'),
                DB::raw('round((i.issued - coalesce(r.returned, 0)) * book_titles.unit_price, 2) as value_outstanding'),
            ]);
    }

    /** How long each open request has sat where it is — the bottleneck report. */
    protected function requestPipeline(): Collection
    {
        return BookRequest::open()
            ->with(['center:id,name', 'campus:id,name,name_en', 'requester:id,name'])
            ->orderBy('updated_at')
            ->get()
            ->map(fn (BookRequest $r) => [
                'request_number' => $r->request_number,
                'destination'    => $r->destination_name,
                'requester'      => $r->requester?->name,
                'status'         => $r->status->label(),
                'quantity'       => $r->total_quantity,
                'amount'         => (float) $r->total_amount,
                'paid'           => $r->paid_amount,
                'days_in_stage'  => (int) round(now()->diffInDays($r->updated_at)),
            ]);
    }

    protected function payments(array $filters): Collection
    {
        return DB::table('book_payments')
            ->join('book_requests', 'book_requests.id', '=', 'book_payments.book_request_id')
            ->leftJoin('centers', 'centers.id', '=', 'book_requests.center_id')
            ->leftJoin('campuses', 'campuses.id', '=', 'book_requests.campus_id')
            ->leftJoin('users', 'users.id', '=', 'book_payments.verified_by')
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('book_payments.paid_on', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('book_payments.paid_on', '<=', $to))
            ->orderByDesc('book_payments.paid_on')
            ->get([
                'book_requests.request_number as request',
                DB::raw('coalesce(centers.name, campuses.name, \'—\') as destination'),
                'book_payments.paid_on as paid_on',
                'book_payments.amount as amount',
                'book_payments.method as method',
                'book_payments.bank_name as bank',
                'book_payments.transaction_reference as transaction_reference',
                'book_payments.crv_number as crv_number',
                'book_payments.status as status',
                DB::raw('coalesce(users.name, \'\') as verified_by'),
            ]);
    }

    protected function auditVariance(): Collection
    {
        return DB::table('stock_audit_lines')
            ->join('stock_audits', 'stock_audits.id', '=', 'stock_audit_lines.stock_audit_id')
            ->join('store_rooms', 'store_rooms.id', '=', 'stock_audits.store_room_id')
            ->join('shelf_sections', 'shelf_sections.id', '=', 'stock_audit_lines.shelf_section_id')
            ->join('book_titles', 'book_titles.id', '=', 'stock_audit_lines.book_title_id')
            ->leftJoin('users', 'users.id', '=', 'stock_audit_lines.counted_by')
            ->whereNotNull('stock_audit_lines.counted_quantity')
            ->whereColumn('stock_audit_lines.counted_quantity', '!=', 'stock_audit_lines.system_quantity')
            ->orderByDesc('stock_audits.created_at')
            ->get([
                'stock_audits.reference as audit',
                'stock_audits.status as status',
                'store_rooms.name as store',
                'shelf_sections.code as section',
                'book_titles.code as code',
                'book_titles.title as title',
                'stock_audit_lines.system_quantity as system_quantity',
                'stock_audit_lines.counted_quantity as counted_quantity',
                DB::raw('(stock_audit_lines.counted_quantity - stock_audit_lines.system_quantity) as variance'),
                DB::raw('coalesce(users.name, \'\') as counted_by'),
                'stock_audit_lines.note as note',
            ]);
    }

    /** Consumption rate against stock left — what to send back to the printer. */
    protected function reprintForecast(): Collection
    {
        return BookTitle::active()
            ->with('stocks')
            ->orderBy('title')
            ->get()
            ->map(fn (BookTitle $t) => [
                'code'             => $t->code,
                'title'            => $t->title,
                'on_hand'          => $t->total_on_hand,
                'reorder_level'    => $t->reorder_level,
                'reorder_quantity' => $t->reorder_quantity,
                'weeks_of_cover'   => $t->weeksOfCover(),
                'action'           => $t->isOutOfStock()
                    ? 'Reprint now — out of stock'
                    : ($t->isLowStock() ? 'Reprint — at or below reorder level' : 'OK'),
            ])
            ->filter(fn (array $row) => $row['action'] !== 'OK')
            ->values();
    }
}
