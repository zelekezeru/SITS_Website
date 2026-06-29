<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Stamps created_by / updated_by from the authenticated user.
 *
 * Columns are detected per table (schema-introspected once per request and
 * cached), so the same trait works on tables that have only created_by
 * (tasks, kpis, job_description_versions) and tables that have only
 * updated_by (settings) without polluting $fillable or risking spoofed
 * mass-assignment of audit columns.
 */
trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function (Model $model): void {
            $model->stampBlame(creating: true);
        });

        static::updating(function (Model $model): void {
            $model->stampBlame(creating: false);
        });
    }

    protected function stampBlame(bool $creating): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        if ($creating && empty($this->created_by) && $this->blameHasColumn('created_by')) {
            $this->created_by = $userId;
        }

        if ($this->blameHasColumn('updated_by')) {
            $this->updated_by = $userId;
        }
    }

    protected function blameHasColumn(string $column): bool
    {
        static $cache = [];

        $key = $this->getTable().'.'.$column;

        return $cache[$key] ??= $this->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($this->getTable(), $column);
    }
}
