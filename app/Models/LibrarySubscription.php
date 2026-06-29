<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibrarySubscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_name',
        'plan_type',
        'amount_paid',
        'start_date',
        'expiry_date',
        'is_active',
        'payment_reference',
        'payment_method',
        'jstore_user_id',
        'jstore_subscription_id',
        'notes',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'expiry_date' => 'date',
        'is_active'   => 'boolean',
        'amount_paid' => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * Subscriptions that are currently active and not expired.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expiry_date')                 // lifetime
                           ->orWhere('expiry_date', '>=', now());     // not yet expired
                     });
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * True if the subscription is currently valid.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->plan_type === 'lifetime') {
            return true;
        }

        return $this->expiry_date && $this->expiry_date->isFuture();
    }

    /**
     * Human-readable days remaining (null = lifetime).
     */
    public function daysRemaining(): ?int
    {
        if ($this->plan_type === 'lifetime' || is_null($this->expiry_date)) {
            return null;
        }

        return (int) max(0, now()->diffInDays($this->expiry_date, false));
    }

    /**
     * Badge colour for the plan type.
     */
    public function planColour(): string
    {
        return match ($this->plan_type) {
            'monthly'  => 'indigo',
            'annual'   => 'violet',
            'lifetime' => 'amber',
            default    => 'slate',
        };
    }
}
