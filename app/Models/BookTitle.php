<?php

namespace App\Models;

use App\Enums\Language;
use App\Models\Concerns\HasTrackingHash;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * A printed course book, tracked as a QUANTITY rather than as individual copies.
 *
 * Not to be confused with {@see Book}, the ILS lending record. See
 * docs/books-inventory-system.md §1 for why the two are separate.
 */
class BookTitle extends Model
{
    use HasFactory, HasTrackingHash, LogsOperationalActivity, Searchable, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'subtitle',
        'description',
        'author',
        'edition',
        'isbn',
        'course_id',
        'course_code',
        'course_name',
        'program_id',
        'language',
        'study_mode_id',
        'page_count',
        'unit_price',
        'unit_cost',
        'reorder_level',
        'reorder_quantity',
        'cover_path',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'language'         => Language::class,
        'unit_price'       => 'decimal:2',
        'unit_cost'        => 'decimal:2',
        'page_count'       => 'integer',
        'reorder_level'    => 'integer',
        'reorder_quantity' => 'integer',
        'is_active'        => 'boolean',
    ];

    protected $appends = ['total_on_hand', 'total_reserved', 'total_available'];

    // ── Relationships ──────────────────────────────────────────────────────

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function studyMode(): BelongsTo
    {
        return $this->belongsTo(StudyMode::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BookStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function printRuns(): HasMany
    {
        return $this->hasMany(PrintRun::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(BookRequestItem::class);
    }

    public function dispatchItems(): HasMany
    {
        return $this->hasMany(BookDispatchItem::class);
    }

    // ── Stock roll-ups ─────────────────────────────────────────────────────

    /** Physical copies on the shelves, across every section. */
    public function getTotalOnHandAttribute(): int
    {
        return (int) ($this->relationLoaded('stocks')
            ? $this->stocks->sum('quantity')
            : $this->stocks()->sum('quantity'));
    }

    /** Held back for requests that have been verified but not yet dispatched. */
    public function getTotalReservedAttribute(): int
    {
        return (int) ($this->relationLoaded('stocks')
            ? $this->stocks->sum('reserved_quantity')
            : $this->stocks()->sum('reserved_quantity'));
    }

    /** What a new request may actually claim. */
    public function getTotalAvailableAttribute(): int
    {
        return max(0, $this->total_on_hand - $this->total_reserved);
    }

    public function isLowStock(): bool
    {
        return $this->total_on_hand <= $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->total_on_hand <= 0;
    }

    /** Weeks of cover left at the observed issue rate, for reprint planning. */
    public function weeksOfCover(int $overDays = 90): ?float
    {
        $issued = (int) $this->movements()
            ->where('type', \App\Enums\StockMovementType::ISSUE->value)
            ->where('occurred_at', '>=', now()->subDays($overDays))
            ->sum('quantity');

        if ($issued <= 0) {
            return null;
        }

        $perWeek = $issued / max(1, $overDays / 7);

        return round($this->total_on_hand / $perWeek, 1);
    }

    /** "Sociology · Distance · Amharic" — the three category axes, joined. */
    public function getCategoryLabelAttribute(): string
    {
        return collect([
            $this->program?->title,
            $this->studyMode?->name,
            $this->language?->label(),
        ])->filter()->join(' · ');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Titles at or below their reorder level (including zero-stock titles). */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw(
            '(select coalesce(sum(quantity), 0) from book_stocks where book_stocks.book_title_id = book_titles.id) <= book_titles.reorder_level'
        );
    }

    public function scopeForCategory(Builder $query, ?int $programId, ?int $studyModeId, ?string $language): Builder
    {
        return $query
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($studyModeId, fn ($q) => $q->where('study_mode_id', $studyModeId))
            ->when($language, fn ($q) => $q->where('language', $language));
    }

    // ── Search ─────────────────────────────────────────────────────────────

    public function searchableAs(): string
    {
        return 'book_titles_index';
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id'          => (int) $this->id,
            'code'        => $this->code,
            'title'       => $this->title,
            'subtitle'    => $this->subtitle,
            'author'      => $this->author,
            'isbn'        => $this->isbn,
            'course_code' => $this->course_code,
            'course_name' => $this->course_name,
            'description' => $this->description,
        ];
    }
}
