<?php

namespace App\Models;

use App\Models\Concerns\HasTrackingHash;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Top of the warehouse tree: StoreRoom → Shelf → ShelfSection. */
class StoreRoom extends Model
{
    use HasFactory, HasTrackingHash, LogsOperationalActivity, SoftDeletes;

    protected $fillable = [
        'campus_id',
        'name',
        'code',
        'location_note',
        'manager_id',
        'is_active',
        'notes',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function shelves(): HasMany
    {
        return $this->hasMany(Shelf::class);
    }

    public function sections(): HasManyThrough
    {
        return $this->hasManyThrough(ShelfSection::class, Shelf::class);
    }

    public function stocks()
    {
        return BookStock::whereIn('shelf_section_id', $this->sections()->select('shelf_sections.id'));
    }

    public function getTotalOnHandAttribute(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }

    /** "Main Store" — the label printed under this room's QR code. */
    public function getQrLabelAttribute(): string
    {
        return trim($this->name.' ('.$this->code.')');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
