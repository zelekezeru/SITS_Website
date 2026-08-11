<?php

namespace App\Models;

use App\Enums\InventoryLocationType;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A node in the physical location tree: campus → store → room → shelf → bin, to
 * whatever depth the building actually has.
 *
 * Deliberately generic rather than three fixed columns, and deliberately separate
 * from the Library's Floor→Row→ShelfBox hierarchy, which is books-only. The
 * shared anchor between the two is `Campus`.
 */
class InventoryLocation extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'campus_id',
        'parent_id',
        'code',
        'name',
        'type',
        'description',
        'custodian_employee_id',
        'is_issuable',
        'is_active',
    ];

    protected $casts = [
        'type' => InventoryLocationType::class,
        'is_issuable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    /** Directly assigned custodian; a shelf usually inherits its store's. */
    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'custodian_employee_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(InventoryUnit::class, 'current_location_id');
    }

    public function movementsIn(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'to_location_id');
    }

    public function movementsOut(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'from_location_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** Locations that can actually hold stock — excludes groupings. */
    public function scopeStorable(Builder $query): Builder
    {
        $storable = array_values(array_filter(
            array_column(InventoryLocationType::cases(), 'value'),
            fn (string $t) => InventoryLocationType::from($t)->isStorable()
        ));

        return $query->whereIn('type', $storable);
    }

    public function ancestors(): array
    {
        $chain = [];
        $node = $this->parent;

        for ($depth = 0; $node && $depth < 12; $depth++) {
            $chain[] = $node;
            $node = $node->parent;
        }

        return $chain;
    }

    /** "Main Campus › Central Store › Room 12 › Shelf B › Bin 3". */
    public function fullPath(string $separator = ' › '): string
    {
        $parts = array_reverse(array_map(fn (self $l) => $l->name, $this->ancestors()));
        $parts[] = $this->name;

        if ($campus = $this->campus) {
            array_unshift($parts, $campus->name_en ?? $campus->name ?? '');
        }

        return implode($separator, array_filter($parts));
    }

    public function descendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    /**
     * The custodian responsible for this node, inherited from the nearest
     * ancestor that names one.
     */
    public function effectiveCustodian(): ?Employee
    {
        if ($this->custodian_employee_id) {
            return $this->custodian;
        }

        foreach ($this->ancestors() as $ancestor) {
            if ($ancestor->custodian_employee_id) {
                return $ancestor->custodian;
            }
        }

        return null;
    }
}
