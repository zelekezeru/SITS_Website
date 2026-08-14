<?php

namespace App\Http\Controllers\Store;

use App\Enums\InventoryMovementType;
use App\Enums\InventoryUnitStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStockMovement;
use App\Models\InventoryUnit;
use App\Services\Inventory\StockLedger;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inter-location transfers — Phase 3.
 *
 * Gated by `transfer inventory` for writing and `view inventory` for viewing.
 */
class TransferController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $transfers = InventoryStockMovement::whereIn('type', [
            InventoryMovementType::TransferOut,
            InventoryMovementType::TransferIn,
        ])->with([
            'item.category:id,name_en',
            'unit:id,asset_tag,serial_number',
            'fromLocation:id,name,code',
            'toLocation:id,name,code',
            'performedBy:id,name',
        ])->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (InventoryStockMovement $m) => [
                'id' => $m->id,
                'type' => $m->type->value,
                'type_label' => $m->type->label(),
                'type_tone' => $m->type->tone(),
                'item_id' => $m->item_id,
                'item_name' => $m->item?->name_en,
                'item_code' => $m->item?->code,
                'category_name' => $m->item?->category?->name_en,
                'tracking_mode' => $m->item?->tracking_mode?->value,
                'unit_of_measure' => $m->item?->unit_of_measure?->value,
                'unit_id' => $m->unit_id,
                'asset_tag' => $m->unit?->asset_tag,
                'quantity' => (float) $m->quantity,
                'abs_quantity' => abs((float) $m->quantity),
                'from_location' => $m->fromLocation?->name,
                'from_location_id' => $m->from_location_id,
                'to_location' => $m->toLocation?->name,
                'to_location_id' => $m->to_location_id,
                'reference' => $m->reference,
                'occurred_at' => $m->occurred_at?->format('Y-m-d H:i'),
                'performed_by' => $m->performedBy?->name,
                'reason' => $m->reason,
                'notes' => $m->notes,
            ]);

        $items = InventoryItem::where('status', 'active')
            ->with(['category:id,name_en'])
            ->orderBy('name_en')
            ->get()
            ->map(fn (InventoryItem $i) => [
                'id' => $i->id,
                'name_en' => $i->name_en,
                'code' => $i->code,
                'category' => $i->category?->name_en,
                'tracking_mode' => $i->tracking_mode->value,
                'unit_of_measure' => $i->unit_of_measure->value,
                'on_hand' => $i->onHand(),
            ]);

        $availableUnits = InventoryUnit::where('status', InventoryUnitStatus::InStore)
            ->with(['item:id,name_en,code', 'location:id,name'])
            ->get()
            ->map(fn (InventoryUnit $u) => [
                'id' => $u->id,
                'item_id' => $u->item_id,
                'asset_tag' => $u->asset_tag,
                'serial_number' => $u->serial_number,
                'condition' => $u->condition->value,
                'location_id' => $u->current_location_id,
                'location_name' => $u->location?->name,
            ]);

        $locations = InventoryLocation::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        return Inertia::render('Store/Transfers', [
            ...$this->shell($request, 'store.transfers'),
            'transfers' => $transfers,
            'items' => $items,
            'availableUnits' => $availableUnits,
            'locations' => $locations,
            'can' => [
                'transfer' => (bool) $user->can('transfer inventory'),
            ],
        ]);
    }

    public function store(Request $request, StockLedger $ledger)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inventory_items,id'],
            'from_location_id' => ['required', 'exists:inventory_locations,id'],
            'to_location_id' => ['required', 'exists:inventory_locations,id', 'different:from_location_id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_id' => ['nullable', 'exists:inventory_units,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'occurred_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movements = $ledger->transfer($data, $request->user());

        return back()->with('success', "Transfer completed under reference {$movements['out']->reference}.");
    }

    private function shell(Request $request, string $routeName): array
    {
        return [
            'module' => StoreNavigation::module($routeName),
            'nav' => PortalContext::for($request->user())['nav'],
        ];
    }
}
