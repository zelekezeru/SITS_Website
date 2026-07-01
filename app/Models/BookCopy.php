<?php

namespace App\Models;

use App\Enums\BookStatus;
use App\Models\Concerns\HasTrackingHash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsOperationalActivity;

class BookCopy extends Model
{
    use HasFactory, HasTrackingHash, LogsOperationalActivity;
    use SoftDeletes;

    protected $fillable = [
        'book_id',
        'current_shelf_box_id',
        'home_campus_id',
        'tracking_hash',
        'barcode',
        'accession_number',
        'status',
        'condition',
        'acquired_on',
        'acquisition_cost'
    ];

    protected $casts = [
        'status'      => BookStatus::class,
        'acquired_on' => 'date',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function shelfBox(): BelongsTo
    {
        return $this->belongsTo(ShelfBox::class, 'current_shelf_box_id');
    }

    public function homeCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'home_campus_id');
    }

    public function placementLogs(): HasMany
    {
        return $this->hasMany(PlacementLog::class);
    }

    public function currentCampus(): ?Campus
    {
        return $this->shelfBox?->row?->floor?->campus;
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeAtCampus($query, $campusId)
    {
        return $query->whereHas('shelfBox.row.floor', fn($s) => $s->where('campus_id', $campusId));
    }

    public function stagingRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StagingCopy::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'book_copy_id');
    }

    public function activeLoan(): HasOne
    {
        return $this->hasOne(Loan::class, 'book_copy_id')->where('status', 'active');
    }
}
