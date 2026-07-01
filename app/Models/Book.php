<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\LogsOperationalActivity;
use Laravel\Scout\Searchable;

class Book extends Model
{
    use HasFactory, LogsOperationalActivity, Searchable;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'isbn',
        'call_number',
        'classification_type',
        'publisher',
        'published_year',
        'edition',
        'page_count',
        'language',
        'subject',
        'description',
        'cover_path',
        'cover_url',
        'notes',
    ];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    public function availableCopiesAtCampus(int $campusId)
    {
        return $this->copies()
            ->where('status', 'available')
            ->whereHas('shelfBox.row.floor', fn($q) => $q->where('campus_id', $campusId));
    }

    public function secureDocuments(): HasMany
    {
        return $this->hasMany(SecureDocument::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(BookReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(BookReview::class)->where('is_approved', true);
    }

    /** Average rating from approved reviews. */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    public function stagingRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StagingBook::class);
    }

    /**
     * Get the index name for the model.
     */
    public function searchableAs(): string
    {
        return 'books_index';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id'             => (int) $this->id,
            'title'          => $this->title,
            'subtitle'       => $this->subtitle,
            'isbn'           => $this->isbn,
            'call_number'    => $this->call_number,
            'description'    => $this->description,
            'publisher'      => $this->publisher,
            'published_year' => (int) $this->published_year,
            'subject'        => $this->subject,
            'authors'        => $this->authors->pluck('name')->join(' '),
            'categories'     => $this->categories->pluck('name')->join(' '),
        ];
    }
}
