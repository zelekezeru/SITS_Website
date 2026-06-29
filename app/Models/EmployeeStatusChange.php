<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeStatusChange extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'from_status',
        'to_status',
        'reason',
        'notes',
        'changed_by',
        'effective_date',
        'changed_at',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'from_status' => EmployeeStatus::class,
        'to_status' => EmployeeStatus::class,
        'effective_date' => 'datetime',
        'changed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function reference()
    {
        return $this->morphTo('reference');
    }

    public function isEffective(): bool
    {
        return $this->effective_date <= now();
    }
}
