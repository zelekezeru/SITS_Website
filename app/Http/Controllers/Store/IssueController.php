<?php

namespace App\Http\Controllers\Store;

use App\Enums\InventoryCondition;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryUnitStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStockMovement;
use App\Models\InventoryUnit;
use App\Services\Inventory\StockLedger;
use App\Support\Inventory\InventoryCodeGenerator;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Issue Vouchers & Returns — Phase 3.
 *
 * Gated by `issue inventory` for issuing, and `view inventory` for viewing.
 */
class IssueController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $movements = InventoryStockMovement::whereIn('type', [
            InventoryMovementType::Issue,
            InventoryMovementType::Return,
        ])->with([
            'item.category:id,name_en',
            'unit:id,asset_tag,serial_number,condition',
            'fromLocation:id,name,code',
            'toLocation:id,name,code',
            'employee:id,full_name_en',
            'request:id,request_number',
            'performedBy:id,name',
        ])->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (InventoryStockMovement $m) => $this->presentMovement($m));

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

        $inUseUnits = InventoryUnit::where('status', InventoryUnitStatus::Issued)
            ->with(['item:id,name_en,code', 'assignedTo:id,full_name_en'])
            ->get()
            ->map(fn (InventoryUnit $u) => [
                'id' => $u->id,
                'item_id' => $u->item_id,
                'asset_tag' => $u->asset_tag,
                'serial_number' => $u->serial_number,
                'condition' => $u->condition->value,
                'employee_id' => $u->assigned_to_employee_id,
                'employee_name' => $u->assignedTo?->full_name_en,
            ]);

        $locations = InventoryLocation::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $employees = Employee::where('is_active', true)
            ->orderBy('full_name_en')
            ->get(['id', 'full_name_en as name', 'staff_no']);

        return Inertia::render('Store/Issues', [
            ...$this->shell($request, 'store.issues'),
            'movements' => $movements,
            'items' => $items,
            'availableUnits' => $availableUnits,
            'inUseUnits' => $inUseUnits,
            'locations' => $locations,
            'employees' => $employees,
            'conditions' => self::enumOptions(InventoryCondition::cases()),
            'can' => [
                'issue' => (bool) $user->can('issue inventory'),
            ],
        ]);
    }

    public function store(Request $request, StockLedger $ledger)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'from_location_id' => ['nullable', 'exists:inventory_locations,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'unit_id' => ['nullable', 'exists:inventory_units,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'occurred_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $ledger->issue($data, $request->user());

        return back()->with('success', "Stock issued under voucher {$movement->reference}.");
    }

    public function storeReturn(Request $request, StockLedger $ledger)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'to_location_id' => ['nullable', 'exists:inventory_locations,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'unit_id' => ['nullable', 'exists:inventory_units,id'],
            'condition' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:50'],
            'occurred_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $ledger->returnStock($data, $request->user());

        return back()->with('success', "Return recorded successfully.");
    }

    private function presentMovement(InventoryStockMovement $m): array
    {
        return [
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
            'serial_number' => $m->unit?->serial_number,
            'quantity' => (float) $m->quantity,
            'abs_quantity' => abs((float) $m->quantity),
            'from_location' => $m->fromLocation?->name,
            'to_location' => $m->toLocation?->name,
            'employee_name' => $m->employee?->full_name_en,
            'request_number' => $m->request?->request_number,
            'reference' => $m->reference,
            'unit_cost' => $m->unit_cost !== null ? (float) $m->unit_cost : null,
            'occurred_at' => $m->occurred_at?->format('Y-m-d H:i'),
            'performed_by' => $m->performedBy?->name,
            'reason' => $m->reason,
            'notes' => $m->notes,
        ];
    }

    private function shell(Request $request, string $routeName): array
    {
        return [
            'module' => StoreNavigation::module($routeName),
            'nav' => PortalContext::for($request->user())['nav'],
        ];
    }

    private static function enumOptions(array $cases): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : $case->name,
        ], $cases);
    }
}
