<?php

namespace App\Models;

use App\Models\Concerns\HasTrackingHash;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShelfBox extends Model
{
    use HasTrackingHash, SoftDeletes, HasFactory, LogsOperationalActivity;

    protected $fillable = ['row_id', 'label', 'tracking_hash', 'capacity'];

    public function row(): BelongsTo
    {
        return $this->belongsTo(Row::class);
    }

    /**
     * Convenience accessors to traverse the hierarchy.
     * Useful for displaying "Main Campus › Ground › Row A › A-01" breadcrumbs.
     */
    public function floor(): ?Floor
    {
        return $this->row?->floor;
    }

    public function campus(): ?Campus
    {
        return $this->row?->floor?->campus;
    }

    public function copies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookCopy::class, 'current_shelf_box_id');
    }
}
