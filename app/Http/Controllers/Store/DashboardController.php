<?php

namespace App\Http\Controllers\Store;

use App\Enums\StorePermission;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page for the Inventory & Asset Management ("Store") portal.
 *
 * Phase 0 ships the access layer, so the dashboard reports what the signed-in
 * user is entitled to do in the store and what each delivery phase unlocks —
 * the live stock tiles (reorder alerts, on-hand valuation, assets on loan) land
 * with the inventory tables in Phase 2. See docs/inventory-management-design.md.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        /** @var array<string, bool> $can */
        $can = [];
        foreach (StorePermission::cases() as $permission) {
            $can[$permission->name] = (bool) $user?->can($permission->value);
        }

        return Inertia::render('Store/Dashboard', [
            'nav' => StoreNavigation::sections($user),
            'can' => $can,
            'reorderAlerts' => InventoryItem::needingReorder()
                ->with('category:id,name_en')
                ->orderBy('name_en')
                ->limit(8)
                ->get()
                ->map(fn (InventoryItem $i) => [
                    'id' => $i->id,
                    'code' => $i->code,
                    'name_en' => $i->name_en,
                    'category' => $i->category?->name_en,
                    'on_hand' => $i->onHand(),
                    'reorder_level' => (float) $i->reorder_level,
                ]),
            'reorderAlertsTotal' => InventoryItem::needingReorder()->count(),
            // The capability list drives the "what you can do here" panel, so it
            // always matches the permissions actually granted — no second copy.
            'capabilities' => collect(StorePermission::cases())
                ->map(fn (StorePermission $p) => [
                    'key' => $p->name,
                    'name' => $p->value,
                    'description' => $p->description(),
                    'granted' => (bool) $user?->can($p->value),
                ])->values(),
            'sections' => collect(StoreNavigation::modules())
                ->filter(fn (array $m) => (bool) $user?->can($m['permission']))
                ->groupBy('section')
                ->map(fn ($items, $label) => [
                    'label' => $label,
                    'items' => $items->map(fn ($m) => [
                        'label' => $m['label'],
                        'path' => $m['path'],
                        'icon' => $m['icon'],
                        'description' => $m['description'],
                    ])->values(),
                ])->values(),
            'roadmap' => self::ROADMAP,
        ]);
    }

    /**
     * Delivery phases, mirroring docs/inventory-management-design.md §8. Kept
     * here (not in the database) because it is documentation, not data.
     */
    private const ROADMAP = [
        ['phase' => 'Phase 0', 'title' => 'Access & roles', 'status' => 'done',
            'detail' => 'Store Keeper role, 15 permissions, portal shell and permission-gated route surface.'],
        ['phase' => 'Phase 1', 'title' => 'Foundation', 'status' => 'done',
            'detail' => 'The 14-table schema, 13 enums, models and code generators; categories, suppliers and locations are live.'],
        ['phase' => 'Phase 2', 'title' => 'Catalog & receiving', 'status' => 'done',
            'detail' => 'Items with photos, goods-received notes, the movement ledger and reorder alerts.'],
        ['phase' => 'Phase 3', 'title' => 'Issue & requisition', 'status' => 'next',
            'detail' => 'Requisition approval flow, issue vouchers, returns and inter-location transfers.'],
        ['phase' => 'Phase 4', 'title' => 'Assets', 'status' => 'planned',
            'detail' => 'Asset register with QR tags, custody handover, maintenance and disposal.'],
        ['phase' => 'Phase 5', 'title' => 'Control & insight', 'status' => 'planned',
            'detail' => 'Stocktake sessions, variance posting, nine reports and the clearance hook.'],
    ];
}
