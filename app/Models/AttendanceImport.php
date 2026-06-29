<?php

namespace App\Models;

use App\Enums\AttendanceImportStatus;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceImport extends Model
{
    use Blameable;

    protected $fillable = [
        'payroll_period_id',
        'original_filename',
        'file_path',
        'status',
        'matched_count',
        'ambiguous_count',
        'unmatched_count',
        'excluded_count',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'status' => AttendanceImportStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(AttendanceImportRow::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPendingReview(): bool
    {
        return $this->status === AttendanceImportStatus::PendingReview;
    }

    /** Rows that still need a human decision before this import can be approved. */
    public function hasUnresolvedRows(): bool
    {
        return $this->rows()
            ->where('is_excluded', false)
            ->whereIn('match_status', ['ambiguous', 'unmatched'])
            ->exists();
    }
}
