<?php

namespace App\Models;

use App\Models\Concerns\HasTrackingHash;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The only level of the warehouse tree that holds stock — the physical slot the
 * store keeper's sticky note ("Sine-Mahiberesb / SM-02 / 26") refers to.
 */
class ShelfSection extends Model
{
    use HasFactory, HasTrackingHash, LogsOperationalActivity, SoftDeletes;

    protected $fillable = ['shelf_id', 'code', 'name', 'capacity', 'sort_order'];

    protected $casts = [
        'capacity'   => 'integer',
        'sort_order' => 'integer',
    ];

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    public function storeRoom(): ?StoreRoom
    {
        return $this->shelf?->storeRoom;
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BookStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** "Main Store › Shelf A › SM-02" — printed under the QR code. */
    public function getPathAttribute(): string
    {
        return collect([
            $this->shelf?->storeRoom?->name,
            $this->shelf?->label ?: $this->shelf?->code,
            $this->name ? "{$this->code} — {$this->name}" : $this->code,
        ])->filter()->join(' › ');
    }

    public function getQrLabelAttribute(): string
    {
        return $this->path;
    }

    public function getTotalOnHandAttribute(): int
    {
        return (int) ($this->relationLoaded('stocks')
            ? $this->stocks->sum('quantity')
            : $this->stocks()->sum('quantity'));
    }

    /** How much room is left, when a capacity was declared. */
    public function getRemainingCapacityAttribute(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->total_on_hand);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
