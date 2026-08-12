<?php

namespace App\Http\Controllers\Store;

use App\Enums\DepreciationMethod;
use App\Enums\InventoryLocationType;
use App\Enums\InventoryTrackingMode;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Employee;
use App\Models\InventoryCategory;
use App\Models\InventoryLocation;
use App\Models\InventorySupplier;
use App\Support\Inventory\InventoryCodeGenerator;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD for the store's reference data: the category tree, suppliers, and the
 * physical location tree. These three have to exist before an item can be
 * catalogued or a receipt recorded, which is why they land first.
 *
 * Read is gated by `view inventory`; writes by the specific manage permission
 * for each entity (see routes/erp.php).
 */
class ReferenceDataController extends Controller
{
    // ==========================================================================
    // CATEGORIES
    // ==========================================================================

    public function categories(Request $request): Response
    {
        $categories = InventoryCategory::with('parent:id,name_en')
            ->withCount('items')
            ->orderBy('name_en')
            ->get()
            ->map(fn (InventoryCategory $c) => [
                'id' => $c->id,
                'parent_id' => $c->parent_id,
                'code' => $c->code,
                'name_en' => $c->name_en,
                'name_am' => $c->name_am,
                'description' => $c->description,
                'tracking_mode' => $c->tracking_mode->value,
                'tracking_mode_label' => $c->tracking_mode->label(),
                'default_depreciation_method' => $c->default_depreciation_method->value,
                'default_useful_life_months' => $c->default_useful_life_months,
                'is_active' => $c->is_active,
                'items_count' => $c->items_count,
                'full_path' => $c->fullPath(),
            ]);

        return Inertia::render('Store/Categories', [
            ...$this->shell($request, 'store.categories'),
            'categories' => $categories,
            'trackingModes' => self::enumOptions(InventoryTrackingMode::cases()),
            'depreciationMethods' => self::enumOptions(DepreciationMethod::cases()),
            'can' => ['manage' => (bool) $request->user()?->can('manage inventory catalog')],
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $this->validateCategory($request);

        InventoryCategory::create($data);

        return back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, InventoryCategory $category)
    {
        $data = $this->validateCategory($request, $category);

        // A category that is its own ancestor makes fullPath() and descendantIds()
        // recurse until the depth guard trips; reject the cycle at the source.
        if (($data['parent_id'] ?? null) && $this->wouldCycle($category, (int) $data['parent_id'])) {
            throw ValidationException::withMessages([
                'parent_id' => 'That parent sits underneath this category — it would create a loop.',
            ]);
        }

        $category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(InventoryCategory $category)
    {
        if ($category->items()->exists()) {
            return back()->with('error', 'This category still has items. Move or archive them first.');
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'This category has sub-categories. Remove them first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    /** @return array<string, mixed> */
    private function validateCategory(Request $request, ?InventoryCategory $category = null): array
    {
        return $request->validate([
            'parent_id' => ['nullable', 'exists:inventory_categories,id', Rule::notIn([$category?->id])],
            'code' => [
                'required', 'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('inventory_categories', 'code')->ignore($category?->id)->withoutTrashed(),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tracking_mode' => ['required', Rule::enum(InventoryTrackingMode::class)],
            'default_depreciation_method' => ['required', Rule::enum(DepreciationMethod::class)],
            'default_useful_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'is_active' => ['boolean'],
        ], [
            'code.regex' => 'The code must be letters and numbers only — it becomes the prefix in SKUs and asset tags.',
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ]);
    }

    /** True when $newParentId sits inside $category's own subtree. */
    private function wouldCycle(InventoryCategory $category, int $newParentId): bool
    {
        return in_array($newParentId, $category->descendantIds(), true);
    }

    // ==========================================================================
    // SUPPLIERS
    // ==========================================================================

    public function suppliers(Request $request): Response
    {
        $suppliers = InventorySupplier::withCount('batches')
            ->withSum('batches as total_spend', 'total_cost')
            ->orderBy('name')
            ->get()
            ->map(fn (InventorySupplier $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'tin' => $s->tin,
                'contact_person' => $s->contact_person,
                'phone' => $s->phone,
                'email' => $s->email,
                'address' => $s->address,
                'city' => $s->city,
                'bank_name' => $s->bank_name,
                'bank_account' => $s->bank_account,
                'rating' => $s->rating,
                'notes' => $s->notes,
                'is_active' => $s->is_active,
                'batches_count' => $s->batches_count,
                'total_spend' => round((float) ($s->total_spend ?? 0), 2),
            ]);

        return Inertia::render('Store/Suppliers', [
            ...$this->shell($request, 'store.suppliers'),
            'suppliers' => $suppliers,
            'summary' => [
                'total' => $suppliers->count(),
                'active' => $suppliers->where('is_active', true)->count(),
                'total_spend' => round($suppliers->sum('total_spend'), 2),
            ],
            'can' => ['manage' => (bool) $request->user()?->can('manage inventory suppliers')],
        ]);
    }

    public function storeSupplier(Request $request)
    {
        $data = $this->validateSupplier($request);
        $data['code'] = InventoryCodeGenerator::supplierCode();
        $data['created_by'] = $request->user()?->id;

        InventorySupplier::create($data);

        return back()->with('success', 'Supplier added.');
    }

    public function updateSupplier(Request $request, InventorySupplier $supplier)
    {
        $supplier->update($this->validateSupplier($request, $supplier));

        return back()->with('success', 'Supplier updated.');
    }

    public function destroySupplier(InventorySupplier $supplier)
    {
        // Receipts reference the supplier for costing and performance reporting;
        // deactivate instead so the history stays intact.
        if ($supplier->batches()->exists()) {
            $supplier->update(['is_active' => false]);

            return back()->with('success', 'Supplier has receipts on file, so it was deactivated rather than deleted.');
        }

        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }

    /** @return array<string, mixed> */
    private function validateSupplier(Request $request, ?InventorySupplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:60'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);
    }

    // ==========================================================================
    // LOCATIONS
    // ==========================================================================

    public function locations(Request $request): Response
    {
        $locations = InventoryLocation::with(['parent:id,name', 'campus:id,name_en', 'custodian:id,full_name_en'])
            ->withCount('units')
            ->orderBy('name')
            ->get();

        return Inertia::render('Store/Locations', [
            ...$this->shell($request, 'store.locations'),
            'locations' => $locations->map(fn (InventoryLocation $l) => [
                'id' => $l->id,
                'parent_id' => $l->parent_id,
                'campus_id' => $l->campus_id,
                'campus' => $l->campus?->name_en,
                'code' => $l->code,
                'name' => $l->name,
                'type' => $l->type->value,
                'type_label' => $l->type->label(),
                'icon' => $l->type->icon(),
                'description' => $l->description,
                'custodian_employee_id' => $l->custodian_employee_id,
                'custodian' => $l->custodian?->full_name_en,
                'is_issuable' => $l->is_issuable,
                'is_active' => $l->is_active,
                'is_storable' => $l->type->isStorable(),
                'units_count' => $l->units_count,
                'full_path' => $l->fullPath(),
            ])->values(),
            'campuses' => Campus::orderBy('name_en')->get(['id', 'name_en']),
            'employees' => Employee::where('is_active', true)
                ->orderBy('full_name_en')
                ->get(['id', 'full_name_en', 'staff_no']),
            'locationTypes' => collect(InventoryLocationType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'icon' => $t->icon(),
                'storable' => $t->isStorable(),
            ])->values(),
            'can' => ['manage' => (bool) $request->user()?->can('manage inventory locations')],
        ]);
    }

    public function storeLocation(Request $request)
    {
        $data = $this->validateLocation($request);

        $campusName = $data['campus_id']
            ? Campus::find($data['campus_id'])?->name_en
            : null;

        $data['code'] = InventoryCodeGenerator::locationCode($campusName);

        InventoryLocation::create($data);

        return back()->with('success', 'Location created.');
    }

    public function updateLocation(Request $request, InventoryLocation $location)
    {
        $data = $this->validateLocation($request, $location);

        if (($data['parent_id'] ?? null) && in_array((int) $data['parent_id'], $location->descendantIds(), true)) {
            throw ValidationException::withMessages([
                'parent_id' => 'That parent sits underneath this location — it would create a loop.',
            ]);
        }

        $location->update($data);

        return back()->with('success', 'Location updated.');
    }

    public function destroyLocation(InventoryLocation $location)
    {
        if ($location->children()->exists()) {
            return back()->with('error', 'This location has sub-locations. Remove them first.');
        }

        if ($location->units()->exists()) {
            return back()->with('error', 'Assets are still recorded here. Move them first.');
        }

        // Ledger history points at locations in both directions; a location that
        // stock has ever passed through is deactivated, never removed.
        if ($location->movementsIn()->exists() || $location->movementsOut()->exists()) {
            $location->update(['is_active' => false]);

            return back()->with('success', 'Location has stock history, so it was deactivated rather than deleted.');
        }

        $location->delete();

        return back()->with('success', 'Location deleted.');
    }

    /** @return array<string, mixed> */
    private function validateLocation(Request $request, ?InventoryLocation $location = null): array
    {
        return $request->validate([
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'parent_id' => ['nullable', 'exists:inventory_locations,id', Rule::notIn([$location?->id])],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(InventoryLocationType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'custodian_employee_id' => ['nullable', 'exists:employees,id'],
            'is_issuable' => ['boolean'],
            'is_active' => ['boolean'],
        ], [
            'parent_id.not_in' => 'A location cannot sit inside itself.',
        ]);
    }

    // ==========================================================================

    /**
     * Props every store page needs: the module metadata for its header, and the
     * sidebar of whichever portal the viewer arrived through.
     *
     * @return array<string, mixed>
     */
    private function shell(Request $request, string $routeName): array
    {
        return [
            'module' => StoreNavigation::module($routeName),
            'nav' => PortalContext::for($request->user())['nav'],
        ];
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private static function enumOptions(array $cases): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : $case->name,
        ], $cases);
    }
}
