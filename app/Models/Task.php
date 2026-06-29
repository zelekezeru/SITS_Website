<?php

namespace App\Models;

use App\Enums\Cadence;
use App\Enums\TaskStatus;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use Blameable, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'target_id',
        'parent_task_id',
        'title',
        'description',
        'cadence',
        'starting_date',
        'due_date',
        'weight',
        'status',
        'completion_pct',
        'assigned_by_id',
    ];

    protected $casts = [
        'cadence' => Cadence::class,
        'status' => TaskStatus::class,
        'starting_date' => 'date',
        'due_date' => 'date',
        'weight' => 'decimal:2',
        'completion_pct' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id');
    }

    /** KPIs this task contributes toward (decoupled via pivot). */
    public function kpis(): BelongsToMany
    {
        return $this->belongsToMany(Kpi::class, 'kpi_task')
            ->withPivot('contribution_weight')
            ->withTimestamps();
    }

    public function fortnights(): BelongsToMany
    {
        return $this->belongsToMany(Fortnight::class, 'fortnight_task')->withTimestamps();
    }

    public function days(): BelongsToMany
    {
        return $this->belongsToMany(Day::class, 'day_task')->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_task')->withTimestamps();
    }

    /** Additional collaborators beyond the primary owner. */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(TaskProgressReport::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
