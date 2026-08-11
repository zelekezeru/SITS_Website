<?php

namespace App\Models;

use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A vendor the Seminary buys from. Referenced by goods-received batches, which
 * is where the supplier-performance report gets its spend, lead time and
 * arrival-condition figures.
 */
class InventorySupplier extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'code',
        'name',
        'tin',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'bank_name',
        'bank_account',
        'rating',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_active' => 'boolean',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'supplier_id');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(InventoryMaintenanceLog::class, 'supplier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Total ever purchased from this supplier, across all receipts. */
    public function totalSpend(): float
    {
        return round((float) $this->batches()->sum('total_cost'), 2);
    }
}
