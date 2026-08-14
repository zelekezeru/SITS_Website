<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\StockAuditStatus;
use App\Http\Controllers\Controller;
use App\Models\BookDispatch;
use App\Models\BookTitle;
use App\Models\Shelf;
use App\Models\ShelfSection;
use App\Models\StockAudit;
use App\Models\StoreRoom;
use App\Services\Bookstore\QrLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The single endpoint every bookstore QR points at.
 *
 * A phone camera opens `/bookstore/scan/{hash}`; this works out what was
 * scanned and sends the user to the screen they meant. Mid-audit, scanning a
 * section jumps straight to that section's count sheet — the reason the audit
 * flow is fast enough to actually be used on the warehouse floor.
 */
class ScanController extends Controller
{
    public function __construct(private readonly QrLabelService $qr)
    {
    }

    /** The scanner page, for staff using a handheld or a laptop webcam. */
    public function index(): Response
    {
        return Inertia::render('Bookstore/Scan/Index');
    }

    public function resolve(string $hash, Request $request): RedirectResponse
    {
        $found = $this->qr->resolve($hash);

        if (! $found) {
            return redirect()
                ->route('bookstore.scan.index')
                ->with('error', 'That code does not match anything in the bookstore.');
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $found['model'];

        return match ($found['type']) {
            'title'   => redirect()->route('bookstore.titles.show', $model),
            'store'   => redirect()->route('bookstore.stores.show', $model),
            'shelf'   => redirect()->route('bookstore.stores.show', ['store' => $model->store_room_id, 'shelf' => $model->id]),
            'section' => $this->afterSectionScan($model, $request),
            'waybill' => redirect()->route('bookstore.dispatches.show', $model),
            default   => redirect()->route('bookstore.dashboard'),
        };
    }

    /** JSON form of the same lookup, for the in-page camera scanner. */
    public function lookup(Request $request): JsonResponse
    {
        $hash = $this->extractHash((string) $request->input('code', ''));

        $found = $hash ? $this->qr->resolve($hash) : null;

        if (! $found) {
            return response()->json(['found' => false], 404);
        }

        $model = $found['model'];

        return response()->json([
            'found'   => true,
            'type'    => $found['type'],
            'caption' => $this->qr->caption($model),
            'url'     => match ($found['type']) {
                'title'   => route('bookstore.titles.show', $model),
                'store'   => route('bookstore.stores.show', $model),
                'shelf'   => route('bookstore.stores.show', $model->store_room_id),
                'section' => route('bookstore.sections.show', $model),
                'waybill' => route('bookstore.dispatches.show', $model),
                default   => route('bookstore.dashboard'),
            },
            'summary' => $this->summarise($found['type'], $model),
        ]);
    }

    /**
     * A scanned QR may arrive as the full URL or as the bare hash, depending on
     * the scanner app. Accept both.
     */
    protected function extractHash(string $code): ?string
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        if (str_contains($code, '/')) {
            $code = (string) preg_replace('#^.*/scan/#', '', $code);
        }

        return preg_match('/^[0-9a-f-]{36}$/i', $code) ? $code : null;
    }

    /** Counting? Go to the count sheet. Otherwise show the section. */
    protected function afterSectionScan(ShelfSection $section, Request $request): RedirectResponse
    {
        $audit = StockAudit::where('status', StockAuditStatus::IN_PROGRESS->value)
            ->where('started_by', $request->user()?->id)
            ->latest('started_at')
            ->first();

        if ($audit && $audit->lines()->where('shelf_section_id', $section->id)->exists()) {
            return redirect()->route('bookstore.audits.show', [
                'audit'   => $audit,
                'section' => $section->id,
            ]);
        }

        return redirect()->route('bookstore.sections.show', $section);
    }

    /** A one-line answer for the scanner overlay, so staff need not navigate. */
    protected function summarise(string $type, $model): string
    {
        return match ($type) {
            'title'   => $model instanceof BookTitle
                ? "{$model->total_on_hand} on hand · {$model->total_available} available"
                : '',
            'section' => $model instanceof ShelfSection
                ? "{$model->total_on_hand} books in this section"
                : '',
            'shelf'   => $model instanceof Shelf
                ? $model->sections()->count().' sections'
                : '',
            'store'   => $model instanceof StoreRoom
                ? "{$model->total_on_hand} books in this store"
                : '',
            'waybill' => $model instanceof BookDispatch
                ? "{$model->total_quantity} books · ".$model->status->label()
                : '',
            default   => '',
        };
    }
}
