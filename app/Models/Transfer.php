<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsOperationalActivity;

class Transfer extends Model
{
    use HasFactory, LogsOperationalActivity, SoftDeletes;

    protected $fillable = [
        'book_copy_id',
        'from_campus_id',
        'to_campus_id',
        'requested_by',
        'approved_by',
        'dispatched_by',
        'received_by',
        'reason_hold_id',
        'reason_loan_id',
        'status',
        'reason',
        'rejection_note',
        'courier_ref',
        'requested_at',
        'approved_at',
        'dispatched_at',
        'received_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function copy()         { return $this->belongsTo(BookCopy::class, 'book_copy_id'); }
    public function fromCampus()   { return $this->belongsTo(Campus::class, 'from_campus_id'); }
    public function toCampus()     { return $this->belongsTo(Campus::class, 'to_campus_id'); }
    public function requester()    { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver()     { return $this->belongsTo(User::class, 'approved_by'); }
    public function dispatcher()   { return $this->belongsTo(User::class, 'dispatched_by'); }
    public function receiver()     { return $this->belongsTo(User::class, 'received_by'); }
    public function hold()         { return $this->belongsTo(Hold::class, 'reason_hold_id'); }
    public function loan()         { return $this->belongsTo(Loan::class, 'reason_loan_id'); }

    public function scopeOpen($q) {
        return $q->whereIn('status', ['requested', 'approved', 'in_transit']);
    }
}
