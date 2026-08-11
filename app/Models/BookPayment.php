<?php

namespace App\Models;

use App\Enums\BookPaymentMethod;
use App\Enums\BookPaymentStatus;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money against a book request.
 *
 * Both references are captured on purpose: the bank/wallet TRANSACTION REFERENCE
 * proves the money moved, and the manual CRV NUMBER ties the entry back to the
 * paper receipt book finance still keeps. The receipt image lives on the private
 * disk and is streamed through a controller — never a public URL.
 */
class BookPayment extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'book_request_id',
        'amount',
        'method',
        'bank_name',
        'transaction_reference',
        'crv_number',
        'receipt_number',
        'paid_on',
        'receipt_image_path',
        'status',
        'recorded_by',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'method'      => BookPaymentMethod::class,
        'status'      => BookPaymentStatus::class,
        'paid_on'     => 'date',
        'verified_at' => 'datetime',
    ];

    protected $hidden = ['receipt_image_path'];

    public function bookRequest(): BelongsTo
    {
        return $this->belongsTo(BookRequest::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function hasReceiptImage(): bool
    {
        return filled($this->receipt_image_path);
    }

    public function isPending(): bool
    {
        return $this->status === BookPaymentStatus::PENDING;
    }

    public function verify(User $verifier, ?string $note = null): void
    {
        $this->update([
            'status'      => BookPaymentStatus::VERIFIED,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'notes'       => $note ?: $this->notes,
        ]);
    }

    public function reject(User $verifier, string $reason): void
    {
        $this->update([
            'status'           => BookPaymentStatus::REJECTED,
            'verified_by'      => $verifier->id,
            'verified_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', BookPaymentStatus::VERIFIED->value);
    }
}
