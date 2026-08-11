<?php

namespace App\Models;

use App\Models\Concerns\HasTrackingHash;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shelf extends Model
{
    use HasFactory, HasTrackingHash, LogsOperationalActivity, SoftDeletes;

    protected $table = 'shelves';

    protected $fillable = ['store_room_id', 'code', 'label', 'capacity', 'sort_order'];

    protected $casts = [
        'capacity'   => 'integer',
        'sort_order' => 'integer',
    ];

    public function storeRoom(): BelongsTo
    {
        return $this->belongsTo(StoreRoom::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ShelfSection::class);
    }

    /** "Main Store › Shelf A" */
    public function getPathAttribute(): string
    {
        return collect([$this->storeRoom?->name, $this->label ?: $this->code])->filter()->join(' › ');
    }

    public function getQrLabelAttribute(): string
    {
        return $this->path;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
