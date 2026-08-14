<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\BookPaymentStatus;
use App\Enums\BookRequestStatus;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\BookPayment;
use App\Models\BookPaymentBypass;
use App\Models\BookRequest;
use App\Models\BookStock;
use App\Models\BookTitle;
use App\Models\StockMovement;
use App\Models\StoreRoom;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** The one screen an administrator opens to know where the books are. */
class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Bookstore/Dashboard', [
            'stats'      => $this->stats(),
            'queue'      => $this->queue(),
            'lowStock'   => $this->lowStock(),
            'movement'   => $this->movementTrend(),
            'byStore'    => $this->byStore(),
            'recent'     => $this->recentMovements(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function stats(): array
    {
        $totals = BookStock::selectRaw('coalesce(sum(quantity), 0) as on_hand, coalesce(sum(reserved_quantity), 0) as reserved')
            ->first();

        $valuation = DB::table('book_stocks')
            ->join('book_titles', 'book_titles.id', '=', 'book_stocks.book_title_id')
            ->selectRaw('coalesce(sum(book_stocks.quantity * book_titles.unit_cost), 0) as at_cost')
            ->selectRaw('coalesce(sum(book_stocks.quantity * book_titles.unit_price), 0) as at_price')
            ->first();

        return [
            'titles'          => BookTitle::active()->count(),
            'on_hand'         => (int) $totals->on_hand,
            'reserved'        => (int) $totals->reserved,
            'available'       => max(0, (int) $totals->on_hand - (int) $totals->reserved),
            'low_stock'       => BookTitle::active()->lowStock()->count(),
            'value_at_cost'   => round((float) $valuation->at_cost, 2),
            'value_at_price'  => round((float) $valuation->at_price, 2),
            'open_requests'   => BookRequest::open()->count(),
            'pending_payments' => BookPayment::where('status', BookPaymentStatus::PENDING->value)->count(),
            // Pay-later: what is awaiting a decision, and what is owed because
            // somebody already decided yes.
            'pending_bypasses' => BookPaymentBypass::pending()->count(),
            'deferred_debt'    => round((float) BookPaymentBypass::outstanding()->sum('amount'), 2),
            'overdue_bypasses' => BookPaymentBypass::outstanding()
                ->whereNotNull('promised_on')
                ->whereDate('promised_on', '<', now())
                ->count(),
        ];
    }

    /** How much work is waiting at each stage, and how long it has waited. */
    protected function queue(): array
    {
        return collect([
            BookRequestStatus::SUBMITTED,
            BookRequestStatus::AWAITING_PAYMENT,
            BookRequestStatus::PAYMENT_VERIFIED,
            BookRequestStatus::APPROVED,
        ])->map(fn (BookRequestStatus $status) => [
            'status'        => $status->value,
            'label'         => $status->label(),
            'color'         => $status->badgeColor(),
            'count'         => BookRequest::awaiting($status)->count(),
            'oldest_days'   => (int) round(
                BookRequest::awaiting($status)->min('updated_at')
                    ? now()->diffInDays(BookRequest::awaiting($status)->min('updated_at'))
                    : 0
            ),
        ])->values()->all();
    }

    protected function lowStock(): array
    {
        return BookTitle::active()
            ->lowStock()
            ->with('stocks')
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn (BookTitle $t) => [
                'id'            => $t->id,
                'code'          => $t->code,
                'title'         => $t->title,
                'on_hand'       => $t->total_on_hand,
                'reorder_level' => $t->reorder_level,
                'out_of_stock'  => $t->isOutOfStock(),
            ])
            ->all();
    }

    /** Received vs issued over the last twelve weeks. */
    protected function movementTrend(): array
    {
        $since = now()->subWeeks(12)->startOfWeek();

        return StockMovement::where('occurred_at', '>=', $since)
            ->get(['type', 'quantity', 'occurred_at'])
            ->groupBy(fn (StockMovement $m) => $m->occurred_at->startOfWeek()->toDateString())
            ->map(fn ($group, $week) => [
                'week'     => $week,
                'received' => (int) $group->where('type', StockMovementType::RECEIPT)->sum('quantity'),
                'issued'   => (int) $group->where('type', StockMovementType::ISSUE)->sum('quantity'),
                'returned' => (int) $group->where('type', StockMovementType::RETURN_IN)->sum('quantity'),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }

    protected function byStore(): array
    {
        return StoreRoom::active()
            ->orderBy('name')
            ->get()
            ->map(fn (StoreRoom $store) => [
                'id'      => $store->id,
                'name'    => $store->name,
                'code'    => $store->code,
                'on_hand' => $store->total_on_hand,
            ])
            ->all();
    }

    protected function recentMovements(): array
    {
        return StockMovement::with(['bookTitle:id,code,title', 'shelfSection:id,code', 'performedBy:id,name'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(fn (StockMovement $m) => [
                'id'          => $m->id,
                'title'       => $m->bookTitle?->title,
                'section'     => $m->shelfSection?->code,
                'type'        => $m->type->value,
                'type_label'  => $m->type->label(),
                'color'       => $m->type->badgeColor(),
                'quantity'    => $m->quantity,
                'signed'      => $m->signed_quantity,
                'by'          => $m->performedBy?->name,
                'occurred_at' => $m->occurred_at?->toIso8601String(),
            ])
            ->all();
    }
}
