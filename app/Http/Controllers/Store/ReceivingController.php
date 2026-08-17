<?php

namespace App\Http\Controllers\Store;

use App\Enums\InventoryCondition;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventorySupplier;
use App\Services\Inventory\StockLedger;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Goods-received notes (GRN). Read is gated by `view inventory`; posting a
 * receipt by `receive inventory`. All writes go through StockLedger.
 */
class ReceivingController extends Controller
{
    public function __construct(private StockLedger $ledger) {}

    public function index(Request $request): Response
    {
        $batches = InventoryBatch::with(['item:id,name_en,code,tracking_mode', 'supplier:id,name', 'location:id,name', 'registeredBy:id,name'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (InventoryBatch $b) => [
                'id' => $b->id,
                'grn_number' => $b->grn_number,
                'item' => $b->item?->name_en,
                'item_code' => $b->item?->code,
                'tracking_mode' => $b->item?->tracking_mode->value,
                'supplier' => $b->supplier?->name,
                'location' => $b->location?->name,
                'quantity_received' => (float) $b->quantity_received,
                'unit_cost' => $b->unit_cost !== null ? (float) $b->unit_cost : null,
                'total_cost' => $b->total_cost !== null ? (float) $b->total_cost : null,
                'purchase_date' => $b->purchase_date?->toDateString(),
                'expiry_date' => $b->expiry_date?->toDateString(),
                'registered_by' => $b->registeredBy?->name,
            ]);

        return Inertia::render('Store/Receipts', [
            ...$this->shell($request, 'store.receipts'),
            'batches' => $batches,
            'items' => InventoryItem::active()
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'code', 'tracking_mode', 'unit_of_measure', 'standard_unit_cost', 'tracks_expiry']),
            'suppliers' => InventorySupplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'locations' => InventoryLocation::storable()->active()->orderBy('name')->get(['id', 'name', 'parent_id']),
            'employees' => Employee::where('is_active', true)->orderBy('full_name_en')->get(['id', 'full_name_en', 'staff_no']),
            'conditions' => collect(InventoryCondition::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->values(),
            'can' => ['receive' => (bool) $request->user()?->can('receive inventory')],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inventory_items,id'],
            'supplier_id' => ['nullable', 'exists:inventory_suppliers,id'],
            'location_id' => ['nullable', 'exists:inventory_locations,id'],
            'quantity_received' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'purchase_date' => ['required', 'date'],
            'production_date' => ['nullable', 'date', 'before_or_equal:purchase_date'],
            'expiry_date' => ['nullable', 'date', 'after:purchase_date'],
            'warranty_until' => ['nullable', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'purchase_order_number' => ['nullable', 'string', 'max:255'],
            'delivery_note_number' => ['nullable', 'string', 'max:255'],
            'condition_on_arrival' => ['nullable', Rule::enum(InventoryCondition::class)],
            'received_by_employee_id' => ['nullable', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $batch = $this->ledger->receive($data, $request->user());

        return back()->with('success', "Receipt {$batch->grn_number} recorded.");
    }

    private function shell(Request $request, string $routeName): array
    {
        return [
            'module' => StoreNavigation::module($routeName),
            'nav' => PortalContext::for($request->user())['nav'],
        ];
    }
}
