<?php

namespace App\Models;

use App\Enums\BookPaymentStatus;
use App\Enums\BookRequestStatus;
use App\Enums\RequestDestination;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The paper "የመጽሃፍት መጠየቂያ ቅጽ", digitised.
 *
 * State changes are NOT made here — they go through
 * {@see \App\Services\Bookstore\BookRequestWorkflow} so that the guard, the
 * reservation and the approval trail can never drift apart.
 */
class BookRequest extends Model
{
    use HasFactory, LogsOperationalActivity, SoftDeletes;

    protected $fillable = [
        'request_number',
        'requester_id',
        'destination_type',
        'center_id',
        'campus_id',
        'student_count',
        'contact_name',
        'contact_phone',
        'status',
        'needed_by',
        'total_quantity',
        'total_amount',
        'notes',
        'rejection_reason',
        'submitted_at',
        'verified_by',
        'verified_at',
        'payment_verified_by',
        'payment_verified_at',
        'approved_by',
        'approved_at',
        'dispatched_by',
        'dispatched_at',
        'received_at',
    ];

    protected $casts = [
        'destination_type'    => RequestDestination::class,
        'status'              => BookRequestStatus::class,
        'student_count'       => 'integer',
        'total_quantity'      => 'integer',
        'total_amount'        => 'decimal:2',
        'needed_by'           => 'date',
        'submitted_at'        => 'datetime',
        'verified_at'         => 'datetime',
        'payment_verified_at' => 'datetime',
        'approved_at'         => 'datetime',
        'dispatched_at'       => 'datetime',
        'received_at'         => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookRequestItem::class);
    }

    /**
     * The approval trail, chronological. Ordered by id as well as acted_at
     * because several stages can land inside the same second and a timestamp
     * alone leaves the trail in an arbitrary order.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(BookRequestApproval::class)->orderBy('acted_at')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BookPayment::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(BookDispatch::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function paymentVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    // ── Derived state ──────────────────────────────────────────────────────

    /** "Halaba Centre" or "Main Campus" — whichever this request is bound for. */
    public function getDestinationNameAttribute(): string
    {
        return $this->destination_type === RequestDestination::CENTER
            ? ($this->center?->name ?? '—')
            : ($this->campus?->name ?? $this->campus?->name_en ?? '—');
    }

    /** Money actually banked and verified against this request. */
    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', BookPaymentStatus::VERIFIED->value)
            ->sum('amount');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return round(max(0, (float) $this->total_amount - $this->paid_amount), 2);
    }

    public function isFullySettled(): bool
    {
        return $this->outstanding_amount <= 0.009;
    }

    /** True once every approved copy has left the store. */
    public function isFullyDispatched(): bool
    {
        return $this->items->every(fn (BookRequestItem $item) => $item->quantity_dispatched >= $item->quantity_approved)
            && $this->items->sum('quantity_approved') > 0;
    }

    /** Recompute the header roll-ups from the lines. */
    public function refreshTotals(): void
    {
        $items = $this->items()->get();

        $this->update([
            'total_quantity' => $items->sum(fn (BookRequestItem $i) => $i->quantity_approved ?: $i->quantity_requested),
            'total_amount'   => round($items->sum(fn (BookRequestItem $i) => (float) $i->line_total), 2),
        ]);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (BookRequestStatus $s) => $s->value,
            BookRequestStatus::open()
        ));
    }

    public function scopeAwaiting(Builder $query, BookRequestStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('requester_id', $user->id);
    }

    /** Next sequential request number, e.g. BR-2026-0007. */
    public static function nextNumber(): string
    {
        $year   = now()->year;
        $prefix = "BR-{$year}-";

        $last = static::withTrashed()
            ->where('request_number', 'like', $prefix.'%')
            ->orderByDesc('request_number')
            ->value('request_number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
