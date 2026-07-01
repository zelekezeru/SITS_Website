<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Automatically generates a UUID tracking_hash on model creation.
 *
 * Used on ShelfBox (Phase 3) and Book (Phase 4).
 * The hash is intentionally generic — works for QR label encoding now,
 * and can be scanned via RFID tags in the future without schema changes.
 */
trait HasTrackingHash
{
    protected static function bootHasTrackingHash(): void
    {
        static::creating(function ($model) {
            if (empty($model->tracking_hash)) {
                $model->tracking_hash = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope to find a model by its tracking hash.
     * Useful for QR/RFID scan lookups.
     */
    public function scopeByTrackingHash($query, string $hash)
    {
        return $query->where('tracking_hash', $hash);
    }
}
