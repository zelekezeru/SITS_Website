<?php

namespace App\Http\Controllers\Store;

use App\Enums\DepreciationMethod;
use App\Enums\InventoryCondition;
use App\Enums\InventoryUnitStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryLocation;
use App\Models\InventoryStockMovement;
use App\Models\InventoryUnit;
use App\Services\Inventory\AssetRegistry;
use App\Services\Inventory\InventoryLabelService;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The fixed-asset register — Phase 4.
 *
 * Every serialized unit with its tag, condition, custody, location, maintenance
 * history and depreciated book value. This is the schedule external auditors ask
 * for, so book value is computed from policy (unit → item → category) rather
 * than stored, and cannot drift from the cost and life it is derived from.
 *
 * Reading needs `view inventory`; editing a unit and printing tags need
 * `manage inventory assets`.
 */
class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $units = InventoryUnit::query()
            ->with([
                'item:id,name_en,name_am,code,category_id,brand,model,unit_of_measure',
                'item.category:id,name_en',
                'location:id,name,code,parent_id',
                'assignedTo:id,full_name_en,staff_no',
                'batch:id,grn_number,supplier_id,purchase_date',
                'batch.supplier:id,name',
                'openAssignment',
            ])
            ->search($request->string('q')->toString() ?: null)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('condition'), fn ($q) => $q->where('condition', $request->string('condition')->toString()))
            ->when($request->filled('location_id'), fn ($q) => $q->where('current_location_id', $request->integer('location_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->whereHas('item', fn ($i) => $i->where('category_id', $request->integer('category_id'))))
            ->orderBy('asset_tag')
            ->get();

        return Inertia::render('Store/Assets', [
            ...$this->shell($request, 'store.assets'),
            'units' => $units->map(fn (InventoryUnit $u) => $this->present($u)),
            'summary' => $this->summary($units),
            'filters' => $request->only(['q', 'status', 'condition', 'location_id', 'category_id']),
            'statuses' => self::enumOptions(InventoryUnitStatus::cases()),
            'conditions' => self::enumOptions(InventoryCondition::cases()),
            'depreciationMethods' => self::enumOptions(DepreciationMethod::cases()),
            'locations' => InventoryLocation::active()->orderBy('name')->get(['id', 'name', 'code', 'type']),
            'categories' => \App\Models\InventoryCategory::where('is_active', true)
                ->orderBy('name_en')->get(['id', 'name_en']),
            'can' => [
                'manage' => (bool) $user->can('manage inventory assets'),
                'dispose' => (bool) $user->can('manage inventory assets'),
            ],
        ]);
    }

    /**
     * One asset, end to end: acquisition, custody chain, maintenance history,
     * ledger movements and its depreciation schedule year by year.
     */
    public function show(Request $request, InventoryUnit $unit): Response
    {
        $unit->load([
            'item.category',
            'location',
            'assignedTo:id,full_name_en,staff_no,department_id',
            'assignedTo.department:id,name_en',
            'batch.supplier',
            'assignments.employee:id,full_name_en,staff_no',
            'assignments.issuedBy:id,name',
            'assignments.receivedBackBy:id,name',
            'maintenanceLogs.supplier:id,name',
            'maintenanceLogs.reportedBy:id,name',
            'disposal.proposedBy:id,name',
            'disposal.approvedBy:id,name',
        ]);

        $movements = InventoryStockMovement::where('unit_id', $unit->id)
            ->with(['fromLocation:id,name', 'toLocation:id,name', 'employee:id,full_name_en', 'performedBy:id,name'])
            ->orderByDesc('occurred_at')->orderByDesc('id')
            ->get()
            ->map(fn (InventoryStockMovement $m) => [
                'id' => $m->id,
                'type' => $m->type->value,
                'type_label' => $m->type->label(),
                'type_tone' => $m->type->tone(),
                'quantity' => (float) $m->quantity,
                'from_location' => $m->fromLocation?->name,
                'to_location' => $m->toLocation?->name,
                'employee_name' => $m->employee?->full_name_en,
                'reference' => $m->reference,
                'occurred_at' => $m->occurred_at?->format('Y-m-d H:i'),
                'performed_by' => $m->performedBy?->name,
                'reason' => $m->reason,
            ]);

        return Inertia::render('Store/AssetDetail', [
            ...$this->shell($request, 'store.assets'),
            'unit' => [
                ...$this->present($unit),
                'notes' => $unit->notes,
                'grn_number' => $unit->batch?->grn_number,
                'supplier_name' => $unit->batch?->supplier?->name,
                'purchase_date' => $unit->batch?->purchase_date?->format('Y-m-d'),
                'in_service_on' => $unit->in_service_on?->format('Y-m-d'),
                'salvage_value' => (float) $unit->salvage_value,
                'total_maintenance_cost' => $unit->totalMaintenanceCost(),
                'department' => $unit->assignedTo?->department?->name_en,
            ],
            'schedule' => $this->depreciationSchedule($unit),
            'assignments' => $unit->assignments->map(fn ($a) => [
                'id' => $a->id,
                'employee_name' => $a->employee?->full_name_en,
                'staff_no' => $a->employee?->staff_no,
                'issued_at' => $a->issued_at?->format('Y-m-d'),
                'due_at' => $a->due_at?->format('Y-m-d'),
                'returned_at' => $a->returned_at?->format('Y-m-d'),
                'condition_out' => $a->condition_out?->label(),
                'condition_in' => $a->condition_in?->label(),
                'damaged' => $a->cameBackDamaged(),
                'overdue' => $a->isOverdue(),
                'days_held' => $a->daysHeld(),
                'issued_by' => $a->issuedBy?->name,
                'received_back_by' => $a->receivedBackBy?->name,
                'purpose' => $a->purpose,
            ]),
            'maintenance' => $unit->maintenanceLogs->map(fn ($l) => [
                'id' => $l->id,
                'type' => $l->type->value,
                'type_label' => $l->type->label(),
                'started_at' => $l->started_at?->format('Y-m-d'),
                'completed_at' => $l->completed_at?->format('Y-m-d'),
                'next_due_at' => $l->next_due_at?->format('Y-m-d'),
                'cost' => $l->cost !== null ? (float) $l->cost : null,
                'serviced_by' => $l->servicedBy(),
                'downtime_days' => $l->effectiveDowntimeDays(),
                'fault_description' => $l->fault_description,
                'outcome' => $l->outcome,
                'is_open' => $l->isOpen(),
            ]),
            'movements' => $movements,
            'disposal' => $unit->disposal ? [
                'reference' => $unit->disposal->reference,
                'method' => $unit->disposal->method->label(),
                'status' => $unit->disposal->status->value,
                'status_label' => $unit->disposal->status->label(),
                'status_tone' => $unit->disposal->status->tone(),
                'reason' => $unit->disposal->reason,
                'book_value' => $unit->disposal->book_value !== null ? (float) $unit->disposal->book_value : null,
                'proceeds' => $unit->disposal->proceeds !== null ? (float) $unit->disposal->proceeds : null,
                'proposed_by' => $unit->disposal->proposedBy?->name,
                'approved_by' => $unit->disposal->approvedBy?->name,
            ] : null,
            'qr' => app(InventoryLabelService::class)->label($unit, 260),
            'conditions' => self::enumOptions(InventoryCondition::cases()),
            'depreciationMethods' => self::enumOptions(DepreciationMethod::cases()),
            'can' => [
                'manage' => (bool) $request->user()->can('manage inventory assets'),
            ],
        ]);
    }

    public function update(Request $request, InventoryUnit $unit, AssetRegistry $registry)
    {
        $data = $request->validate([
            'serial_number' => ['nullable', 'string', 'max:120'],
            'condition' => ['nullable', Rule::in(array_column(InventoryCondition::cases(), 'value'))],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'depreciation_method' => ['nullable', Rule::in(array_column(DepreciationMethod::cases(), 'value'))],
            'useful_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'in_service_on' => ['nullable', 'date'],
            'warranty_until' => ['nullable', 'date'],
            'next_maintenance_due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $registry->updateUnit($unit, $data);

        return back()->with('success', "Asset {$unit->asset_tag} updated.");
    }

    /**
     * Printable QR label sheet. Prints the current filter selection when no
     * explicit ids are given, so "print tags for everything received today" is
     * one filter and one button rather than a hand-typed list.
     */
    public function labels(Request $request, InventoryLabelService $labels)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        $units = InventoryUnit::query()
            ->with(['item:id,name_en,code', 'location:id,name'])
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))
            ->when($ids === [], fn ($q) => $q
                ->inRegister()
                ->when($request->filled('location_id'), fn ($l) => $l->where('current_location_id', $request->integer('location_id')))
                ->when($request->filled('status'), fn ($s) => $s->where('status', $request->string('status')->toString()))
                ->limit(180)) // one print job, not the whole register by accident
            ->orderBy('asset_tag')
            ->get();

        if ($units->isEmpty()) {
            return back()->withErrors(['ids' => 'No assets matched — nothing to print.']);
        }

        return Pdf::loadView('store.print.asset-labels', [
            'title' => 'Asset Tag Labels',
            'labels' => $labels->sheet($units),
        ])->setPaper('a4')->download('asset-labels-'.now()->format('Ymd-His').'.pdf');
    }

    // ---- Presentation --------------------------------------------------------

    private function present(InventoryUnit $u): array
    {
        return [
            'id' => $u->id,
            'asset_tag' => $u->asset_tag,
            'serial_number' => $u->serial_number,
            'item_id' => $u->item_id,
            'item_name' => $u->item?->name_en,
            'item_code' => $u->item?->code,
            'brand' => $u->item?->brand,
            'model' => $u->item?->model,
            'category' => $u->item?->category?->name_en,
            'status' => $u->status->value,
            'status_label' => $u->status->label(),
            'status_tone' => $u->status->tone(),
            'condition' => $u->condition->value,
            'condition_label' => $u->condition->label(),
            'condition_tone' => $u->condition->tone(),
            'location_id' => $u->current_location_id,
            'location_name' => $u->location?->name,
            'holder_id' => $u->assigned_to_employee_id,
            'holder_name' => $u->assignedTo?->full_name_en,
            'holder_staff_no' => $u->assignedTo?->staff_no,
            'assigned_at' => $u->assigned_at?->format('Y-m-d'),
            'due_at' => $u->openAssignment?->due_at?->format('Y-m-d'),
            'is_overdue' => $u->isOverdue(),
            'purchase_cost' => $u->purchase_cost !== null ? (float) $u->purchase_cost : null,
            'depreciation_method' => $u->effectiveDepreciationMethod()->value,
            'depreciation_label' => $u->effectiveDepreciationMethod()->label(),
            'useful_life_months' => $u->useful_life_months,
            'months_in_service' => $u->monthsInService(),
            'accumulated_depreciation' => $u->accumulatedDepreciation(),
            'book_value' => $u->bookValue(),
            'warranty_until' => $u->warranty_until?->format('Y-m-d'),
            'under_warranty' => $u->isUnderWarranty(),
            'next_maintenance_due_at' => $u->next_maintenance_due_at?->format('Y-m-d'),
            'maintenance_due' => $u->isMaintenanceDue(),
        ];
    }

    /**
     * Headline figures for the register. Computed over the filtered collection
     * that is already in memory, so filtering the table also filters the totals
     * — an auditor reading "book value 412,000" expects it to describe the rows
     * beneath it, not the whole database.
     *
     * @param  \Illuminate\Support\Collection<int, InventoryUnit>  $units
     */
    private function summary($units): array
    {
        return [
            'total' => $units->count(),
            'in_store' => $units->where('status', InventoryUnitStatus::InStore)->count(),
            'out' => $units->filter(fn (InventoryUnit $u) => $u->status->isOut())->count(),
            'overdue' => $units->filter(fn (InventoryUnit $u) => $u->isOverdue())->count(),
            'maintenance_due' => $units->filter(fn (InventoryUnit $u) => $u->isMaintenanceDue())->count(),
            'purchase_value' => round($units->sum(fn (InventoryUnit $u) => (float) $u->purchase_cost), 2),
            'book_value' => round($units->sum(fn (InventoryUnit $u) => $u->bookValue()), 2),
            'accumulated_depreciation' => round($units->sum(fn (InventoryUnit $u) => $u->accumulatedDepreciation()), 2),
        ];
    }

    /**
     * Year-by-year depreciation for one asset. Derived from the same
     * DepreciationMethod::accumulated() the register totals use, so the schedule
     * and the summary can never tell different stories.
     *
     * @return array<int, array<string, mixed>>
     */
    private function depreciationSchedule(InventoryUnit $unit): array
    {
        $cost = (float) ($unit->purchase_cost ?? 0);
        $life = $unit->useful_life_months ?? $unit->item?->effectiveUsefulLifeMonths() ?? 0;
        $method = $unit->effectiveDepreciationMethod();

        if ($cost <= 0 || $life <= 0 || $method === DepreciationMethod::None) {
            return [];
        }

        $salvage = (float) $unit->salvage_value;
        $start = $unit->in_service_on ?? $unit->batch?->purchase_date ?? $unit->created_at;
        $elapsed = $unit->monthsInService();
        $years = (int) ceil($life / 12);
        $rows = [];
        $previous = 0.0;

        for ($year = 1; $year <= $years; $year++) {
            $months = min($year * 12, $life);
            $accumulated = $method->accumulated($cost, $salvage, $life, $months);

            $rows[] = [
                'year' => $year,
                'period' => $start ? $start->copy()->addMonths(($year - 1) * 12)->format('M Y')
                    .' – '.$start->copy()->addMonths($months)->format('M Y') : null,
                'charge' => round($accumulated - $previous, 2),
                'accumulated' => round($accumulated, 2),
                'book_value' => round(max($cost - $accumulated, $salvage), 2),
                'is_current' => $elapsed > ($year - 1) * 12 && $elapsed <= $months,
            ];

            $previous = $accumulated;
        }

        return $rows;
    }

    private function shell(Request $request, string $routeName): array
    {
        return [
            'module' => StoreNavigation::module($routeName),
            'nav' => PortalContext::for($request->user())['nav'],
        ];
    }

    /** @param array<int, \BackedEnum> $cases */
    private static function enumOptions(array $cases): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : $case->name,
        ], $cases);
    }
}
