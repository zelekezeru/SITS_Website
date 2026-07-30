<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrityAuditLog extends Model
{
    use HasFactory;

    protected $table = 'integrity_audit_log';

    protected $fillable = [
        'report_id',
        'user_id',
        'action',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(IntegrityReport::class, 'report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(IntegrityReport $report, User $user, string $action, array $meta = []): self
    {
        return static::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
            'action' => $action,
            'meta' => $meta,
        ]);
    }
}
