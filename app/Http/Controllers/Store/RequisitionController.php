<?php

namespace App\Http\Controllers\Store;

use App\Enums\InventoryCondition;
use App\Enums\InventoryRequestStatus;
use App\Enums\InventoryUnitStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRequest;
use App\Models\InventoryRequestLine;
use App\Models\InventoryUnit;
use App\Services\Inventory\StockLedger;
use App\Support\Inventory\InventoryCodeGenerator;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff Requisitions — maker-checker workflow (Phase 3).
 *
 * Requesting: any employee with `request inventory`.
 * Approving: Dept Head / Operations / President (`approve inventory requests`).
 * Issuing: Store Keeper (`issue inventory`).
 */
class RequisitionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();
        $canApprove = (bool) $user->can('approve inventory requests');
        $canIssue = (bool) $user->can('issue inventory');
        $canViewAll = $canApprove || $canIssue || $user->can('view inventory');

        $query = InventoryRequest::with([
            'requester.department',
            'department',
            'approvedBy:id,name',
            'issuedBy:id,name',
            'lines.item.category:id,name_en',
            'lines.unit:id,asset_tag,serial_number,condition',
        ])->orderByDesc('created_at');

        if (! $canViewAll && $employee) {
            $query->where('requested_by_employee_id', $employee->id);
        }

        $requests = $query->get()->map(fn (InventoryRequest $r) => $this->present($r));

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

        $departments = Department::where('is_active', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en as name']);

        return Inertia::render('Store/Requests', [
            ...$this->shell($request, 'store.requests'),
            'requests' => $requests,
            'items' => $items,
            'availableUnits' => $availableUnits,
            'locations' => $locations,
            'departments' => $departments,
            'currentEmployee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->full_name_en,
                'department_id' => $employee->department_id,
            ] : null,
            'can' => [
                'request' => (bool) $user->can('request inventory'),
                'approve' => $canApprove,
                'issue' => $canIssue,
                'viewAll' => $canViewAll,
            ],
            'statuses' => self::enumOptions(InventoryRequestStatus::cases()),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $data = $request->validate([
            'requested_by_employee_id' => ['nullable', 'exists:employees,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'purpose' => ['required', 'string', 'max:500'],
            'needed_by' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'submit_now' => ['boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:inventory_items,id'],
            'lines.*.unit_id' => ['nullable', 'exists:inventory_units,id'],
            'lines.*.quantity_requested' => ['required', 'numeric', 'min:0.001'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $employeeId = $data['requested_by_employee_id'] ?? $employee?->id;
        $departmentId = $data['department_id'] ?? $employee?->department_id;
        $submitNow = (bool) ($data['submit_now'] ?? true);

        return DB::transaction(function () use ($data, $employeeId, $departmentId, $submitNow) {
            $inventoryRequest = InventoryRequest::create([
                'request_number' => InventoryCodeGenerator::requestNumber(),
                'requested_by_employee_id' => $employeeId,
                'department_id' => $departmentId,
                'purpose' => $data['purpose'],
                'needed_by' => $data['needed_by'] ?? null,
                'status' => $submitNow ? InventoryRequestStatus::Submitted : InventoryRequestStatus::Draft,
                'submitted_at' => $submitNow ? now() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['lines'] as $lineData) {
                $inventoryRequest->lines()->create([
                    'item_id' => $lineData['item_id'],
                    'unit_id' => $lineData['unit_id'] ?? null,
                    'quantity_requested' => $lineData['quantity_requested'],
                    'note' => $lineData['note'] ?? null,
                ]);
            }

            return back()->with('success', "Requisition {$inventoryRequest->request_number} recorded.");
        });
    }

    public function submit(InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== InventoryRequestStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'Only draft requisitions can be submitted.']);
        }

        $inventoryRequest->update([
            'status' => InventoryRequestStatus::Submitted,
            'submitted_at' => now(),
        ]);

        return back()->with('success', "Requisition {$inventoryRequest->request_number} submitted for approval.");
    }

    public function approve(Request $request, InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== InventoryRequestStatus::Submitted) {
            throw ValidationException::withMessages(['status' => 'Only submitted requisitions can be approved.']);
        }

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'exists:inventory_request_lines,id'],
            'lines.*.quantity_approved' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $inventoryRequest, $request) {
            foreach ($data['lines'] as $lineData) {
                $line = $inventoryRequest->lines()->where('id', $lineData['id'])->firstOrFail();
                $approved = (float) $lineData['quantity_approved'];

                if ($approved > (float) $line->quantity_requested + 1e-9) {
                    throw ValidationException::withMessages([
                        'lines' => "Approved quantity ({$approved}) cannot exceed requested ({$line->quantity_requested}).",
                    ]);
                }

                $line->update(['quantity_approved' => $approved]);
            }

            $inventoryRequest->update([
                'status' => InventoryRequestStatus::Approved,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
        });

        return back()->with('success', "Requisition {$inventoryRequest->request_number} approved.");
    }

    public function reject(Request $request, InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== InventoryRequestStatus::Submitted) {
            throw ValidationException::withMessages(['status' => 'Only submitted requisitions can be rejected.']);
        }

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $inventoryRequest->update([
            'status' => InventoryRequestStatus::Rejected,
            'rejection_reason' => $data['rejection_reason'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', "Requisition {$inventoryRequest->request_number} rejected.");
    }

    public function cancel(InventoryRequest $inventoryRequest)
    {
        if (! in_array($inventoryRequest->status, [InventoryRequestStatus::Draft, InventoryRequestStatus::Submitted], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or submitted requisitions can be cancelled.']);
        }

        $inventoryRequest->update([
            'status' => InventoryRequestStatus::Cancelled,
        ]);

        return back()->with('success', "Requisition {$inventoryRequest->request_number} cancelled.");
    }

    public function issueStock(Request $request, InventoryRequest $inventoryRequest, StockLedger $ledger)
    {
        if (! $inventoryRequest->isIssuable()) {
            throw ValidationException::withMessages(['status' => 'Requisition is not in an issuable state.']);
        }

        $data = $request->validate([
            'issues' => ['required', 'array', 'min:1'],
            'issues.*.line_id' => ['required', 'exists:inventory_request_lines,id'],
            'issues.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'issues.*.from_location_id' => ['nullable', 'exists:inventory_locations,id'],
            'issues.*.unit_id' => ['nullable', 'exists:inventory_units,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $reference = $data['reference'] ?? InventoryCodeGenerator::issueVoucher();

        DB::transaction(function () use ($data, $inventoryRequest, $ledger, $request, $reference) {
            foreach ($data['issues'] as $issueItem) {
                $line = $inventoryRequest->lines()->where('id', $issueItem['line_id'])->firstOrFail();

                $ledger->issue([
                    'item_id' => $line->item_id,
                    'quantity' => (float) $issueItem['quantity'],
                    'from_location_id' => $issueItem['from_location_id'] ?? null,
                    'unit_id' => $issueItem['unit_id'] ?? $line->unit_id,
                    'employee_id' => $inventoryRequest->requested_by_employee_id,
                    'request_id' => $inventoryRequest->id,
                    'request_line_id' => $line->id,
                    'reference' => $reference,
                    'reason' => "Issue for Requisition {$inventoryRequest->request_number}",
                    'notes' => $data['notes'] ?? null,
                ], $request->user());
            }
        });

        return back()->with('success', "Issued against Requisition {$inventoryRequest->request_number} under voucher {$reference}.");
    }

    private function present(InventoryRequest $r): array
    {
        return [
            'id' => $r->id,
            'request_number' => $r->request_number,
            'status' => $r->status->value,
            'status_label' => $r->status->label(),
            'status_tone' => $r->status->tone(),
            'purpose' => $r->purpose,
            'needed_by' => $r->needed_by?->toDateString(),
            'submitted_at' => $r->submitted_at?->format('Y-m-d H:i'),
            'approved_at' => $r->approved_at?->format('Y-m-d H:i'),
            'fulfilled_at' => $r->fulfilled_at?->format('Y-m-d H:i'),
            'rejection_reason' => $r->rejection_reason,
            'notes' => $r->notes,
            'requester_name' => $r->requester?->full_name_en,
            'requester_employee_id' => $r->requested_by_employee_id,
            'department_name' => $r->department?->name_en ?? $r->requester?->department?->name_en,
            'department_id' => $r->department_id,
            'approved_by_name' => $r->approvedBy?->name,
            'issued_by_name' => $r->issuedBy?->name,
            'is_editable' => $r->isEditable(),
            'is_issuable' => $r->isIssuable(),
            'lines' => $r->lines->map(fn (InventoryRequestLine $l) => [
                'id' => $l->id,
                'item_id' => $l->item_id,
                'item_name' => $l->item?->name_en,
                'item_code' => $l->item?->code,
                'category_name' => $l->item?->category?->name_en,
                'tracking_mode' => $l->item?->tracking_mode?->value,
                'unit_of_measure' => $l->item?->unit_of_measure?->value,
                'unit_id' => $l->unit_id,
                'asset_tag' => $l->unit?->asset_tag,
                'serial_number' => $l->unit?->serial_number,
                'quantity_requested' => (float) $l->quantity_requested,
                'quantity_approved' => $l->quantity_approved !== null ? (float) $l->quantity_approved : null,
                'quantity_issued' => (float) $l->quantity_issued,
                'outstanding' => $l->outstanding(),
                'note' => $l->note,
            ])->values(),
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
