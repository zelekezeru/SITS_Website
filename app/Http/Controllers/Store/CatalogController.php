<?php

namespace App\Http\Controllers\Store;

use App\Enums\DepreciationMethod;
use App\Enums\InventoryItemStatus;
use App\Enums\InventoryTrackingMode;
use App\Enums\UnitOfMeasure;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Support\DocumentUploader;
use App\Support\Inventory\InventoryCodeGenerator;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The item catalog — Phase 2. Read is gated by `view inventory`; writes by
 * `manage inventory catalog`. See docs/inventory-management-design.md §2, §8.
 */
class CatalogController extends Controller
{
    public function items(Request $request): Response
    {
        $items = InventoryItem::with(['category:id,name_en', 'primaryImage', 'documents'])
            ->withCount(['batches', 'units'])
            ->orderBy('name_en')
            ->get()
            ->map(fn (InventoryItem $i) => $this->present($i));

        return Inertia::render('Store/Items', [
            ...$this->shell($request, 'store.items'),
            'items' => $items,
            'categories' => InventoryCategory::where('is_active', true)
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'tracking_mode', 'default_depreciation_method', 'default_useful_life_months']),
            'trackingModes' => self::enumOptions(InventoryTrackingMode::cases()),
            'unitsOfMeasure' => self::enumOptions(UnitOfMeasure::cases()),
            'statuses' => self::enumOptions(InventoryItemStatus::cases()),
            'depreciationMethods' => self::enumOptions(DepreciationMethod::cases()),
            'can' => ['manage' => (bool) $request->user()?->can('manage inventory catalog')],
        ]);
    }

    public function storeItem(Request $request)
    {
        $data = $this->validateItem($request);
        $category = InventoryCategory::findOrFail($data['category_id']);
        unset($data['image']);

        $item = InventoryItem::create([
            ...$data,
            'code' => InventoryCodeGenerator::itemCode($category),
            'created_by' => $request->user()->id,
        ]);

        $this->attachImageIfPresent($request, $item);

        return back()->with('success', "Item created as {$item->code}.");
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        $data = $this->validateItem($request, $item);
        unset($data['image']);

        $item->update($data);

        $this->attachImageIfPresent($request, $item);

        return back()->with('success', 'Item updated.');
    }

    public function destroyItem(InventoryItem $item)
    {
        // A transaction history (a receipt, a unit, a movement) must stay
        // readable, so an item that has ever moved is archived, not deleted —
        // the same protective pattern as categories, suppliers and locations.
        if ($item->batches()->exists() || $item->units()->exists() || $item->movements()->exists()) {
            $item->update(['status' => InventoryItemStatus::Archived]);

            return back()->with('success', 'This item has transaction history, so it was archived rather than deleted.');
        }

        $item->delete();

        return back()->with('success', 'Item deleted.');
    }

    public function storeItemDocument(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(['image', 'invoice', 'warranty', 'manual'])],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $document = DocumentUploader::store(
            title: $data['title'],
            documentableType: InventoryItem::class,
            documentableId: $item->id,
            file: $request->file('file'),
            filePath: null,
            uploadedBy: $request->user()->id,
            category: $data['category'],
        );

        if ($data['category'] === 'image' && $item->primary_image_id === null) {
            $item->update(['primary_image_id' => $document->id]);
        }

        return back()->with('success', 'Document attached.');
    }

    public function destroyItemDocument(InventoryItem $item, Document $document)
    {
        abort_unless(
            $document->documentable_type === InventoryItem::class && $document->documentable_id === $item->id,
            404
        );

        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    // ==========================================================================

    private function attachImageIfPresent(Request $request, InventoryItem $item): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $document = DocumentUploader::store(
            title: $item->name_en.' — photo',
            documentableType: InventoryItem::class,
            documentableId: $item->id,
            file: $request->file('image'),
            filePath: null,
            uploadedBy: $request->user()->id,
            category: 'image',
        );

        $item->update(['primary_image_id' => $document->id]);
    }

    /** @return array<string, mixed> */
    private function validateItem(Request $request, ?InventoryItem $item = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:inventory_categories,id'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tracking_mode' => ['required', Rule::enum(InventoryTrackingMode::class)],
            'unit_of_measure' => ['required', Rule::enum(UnitOfMeasure::class)],
            'status' => ['required', Rule::enum(InventoryItemStatus::class)],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'specification' => ['nullable', 'string', 'max:2000'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'standard_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'tracks_expiry' => ['boolean'],
            'depreciation_method' => ['nullable', Rule::enum(DepreciationMethod::class)],
            'useful_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    /** @return array<string, mixed> */
    private function present(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'code' => $item->code,
            'category_id' => $item->category_id,
            'category' => $item->category?->name_en,
            'name_en' => $item->name_en,
            'name_am' => $item->name_am,
            'description' => $item->description,
            'tracking_mode' => $item->tracking_mode->value,
            'tracking_mode_label' => $item->tracking_mode->label(),
            'unit_of_measure' => $item->unit_of_measure->value,
            'unit_of_measure_label' => $item->unit_of_measure->label(),
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'status_tone' => $item->status->tone(),
            'brand' => $item->brand,
            'model' => $item->model,
            'specification' => $item->specification,
            'reorder_level' => (float) $item->reorder_level,
            'reorder_quantity' => $item->reorder_quantity !== null ? (float) $item->reorder_quantity : null,
            'standard_unit_cost' => $item->standard_unit_cost !== null ? (float) $item->standard_unit_cost : null,
            'tracks_expiry' => $item->tracks_expiry,
            'depreciation_method' => $item->depreciation_method?->value,
            'useful_life_months' => $item->useful_life_months,
            'notes' => $item->notes,
            'image_path' => $item->primaryImage?->path,
            // Deliberate N+1 (one onHand() query per row): catalog sizes here are in
            // the hundreds, not thousands. A cached balance is explicitly deferred to
            // Phase 6 in docs/inventory-management-design.md §1 — don't add one here.
            'on_hand' => $item->onHand(),
            'needs_reorder' => $item->needsReorder(),
            'batches_count' => $item->batches_count,
            'units_count' => $item->units_count,
            'documents' => $item->documents->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'category' => $d->category,
                'path' => $d->path,
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
