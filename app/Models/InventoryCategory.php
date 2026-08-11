<?php

namespace App\Models;

use App\Enums\DepreciationMethod;
use App\Enums\InventoryTrackingMode;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A node in the inventory category tree (Furniture → Chairs → Stackable).
 *
 * Carries the defaults items in the category inherit — tracking mode and
 * depreciation policy — so adding the 40th laptop doesn't mean re-entering the
 * policy. Its `code` is the prefix in item SKUs and asset tags (IT-00184,
 * SITS-IT-000871).
 */
class InventoryCategory extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'parent_id',
        'code',
        'name_en',
        'name_am',
        'description',
        'tracking_mode',
        'default_depreciation_method',
        'default_useful_life_months',
        'is_active',
    ];

    protected $casts = [
        'tracking_mode' => InventoryTrackingMode::class,
        'default_depreciation_method' => DepreciationMethod::class,
        'default_useful_life_months' => 'integer',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name_en');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** Ancestors nearest-first, walking up the tree. */
    public function ancestors(): array
    {
        $chain = [];
        $node = $this->parent;

        // Depth-guarded: a cycle from a bad parent_id would otherwise hang here.
        for ($depth = 0; $node && $depth < 12; $depth++) {
            $chain[] = $node;
            $node = $node->parent;
        }

        return $chain;
    }

    /** "Equipment › IT › Laptops" — the breadcrumb shown in pickers. */
    public function fullPath(string $separator = ' › '): string
    {
        $names = array_reverse(array_map(fn (self $c) => $c->name_en, $this->ancestors()));
        $names[] = $this->name_en;

        return implode($separator, $names);
    }

    /** Every descendant id including this one — for filtering items by branch. */
    public function descendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }
}
