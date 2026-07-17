<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // Website (public site) fields
        'name',
        'email',
        'password',
        'phone',
        'role',
        'profile_image',
        // ERP (PMS) account-lifecycle fields
        'is_approved',
        'is_active',
        'password_changed',
        'default_password',
        'password_reset_requested_at',
        // Library (ILS) field
        'current_campus_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'default_password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'password_changed' => 'boolean',
            // Encrypted at rest; still admin-recoverable in plaintext via this
            // cast, but never stored as a raw string in the database.
            'default_password' => 'encrypted',
            'password_reset_requested_at' => 'datetime',
        ];
    }

    /**
     * The user may log in to ERP areas only once approved by an admin and
     * still active. Public website users are auto-approved on registration.
     */
    public function canLogin(): bool
    {
        return $this->is_approved && $this->is_active;
    }

    // ----- ERP relationships --------------------------------------------

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /** Departments this user heads (departments.head_user_id). */
    public function headedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'head_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // ----- Website (library) relationships ------------------------------

    /** All library subscriptions for the user. */
    public function librarySubscriptions(): HasMany
    {
        return $this->hasMany(LibrarySubscription::class);
    }

    /** The current active library subscription (if any). */
    public function activeLibrarySubscription(): ?LibrarySubscription
    {
        return $this->librarySubscriptions()->active()->latest()->first();
    }

    /**
     * True if the user currently has an active library subscription.
     * Admins and librarians always have access without a subscription.
     */
    public function hasLibraryAccess(): bool
    {
        if ($this->hasAnyRole(['SUPERADMIN', 'ADMIN', 'LIBRARIAN', 'President / Super Admin'])) {
            return true;
        }

        return $this->librarySubscriptions()->active()->exists();
    }

    // ----- Scoping helpers (used by ERP policies) -----------------------

    /**
     * Departments this user may manage: those they head, plus their own
     * department (department scoping is gated by permission, so this is only
     * consulted for roles that actually carry "manage department …").
     *
     * @return array<int, int>
     */
    public function managedDepartmentIds(): array
    {
        $ids = $this->headedDepartments()->pluck('id')->all();

        if ($own = $this->employee?->department_id) {
            $ids[] = $own;
        }

        return array_values(array_unique($ids));
    }

    public function managesDepartment(?int $departmentId): bool
    {
        return $departmentId !== null
            && in_array($departmentId, $this->managedDepartmentIds(), true);
    }

    // ----- Library (ILS) relationships ----------------------------------
    // Merged from sits-library: a user is also a library patron.

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Fine::class);
    }

    public function currentCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'current_campus_id');
    }

    public function stagingRecord(): HasOne
    {
        return $this->hasOne(StagingUser::class);
    }

    /**
     * The user's primary role as a library Role enum. The unified SITS roles
     * use different names (LIBRARIAN, TRAINER, …) than the library enum's
     * values, so they are translated here; tryFrom keeps any legacy lowercase
     * names graceful during the unified-RBAC transition.
     */
    public function primaryRole(): ?Role
    {
        $roleName = $this->roles->first()?->name;

        return match ($roleName) {
            'SUPERADMIN', 'President / Super Admin' => Role::SUPER_ADMIN,
            'ADMIN'             => Role::CAMPUS_ADMIN,
            'LIBRARIAN'         => Role::LIBRARIAN,
            'TRAINER'           => Role::INSTRUCTOR,
            'STAFF', 'EDITOR'   => Role::STAFF_USER,
            'STUDENT'           => Role::STUDENT,
            default             => $roleName ? Role::tryFrom($roleName) : null,
        };
    }

    /** Count of currently active (unreturned) loans. */
    public function getActiveLoansCountAttribute(): int
    {
        return $this->loans()->where('status', 'active')->count();
    }

    /** Total outstanding fine balance. */
    public function getOutstandingFinesTotalAttribute(): float
    {
        return round((float) $this->fines()
            ->where('status', 'open')
            ->sum(DB::raw('amount - paid_amount')), 2);
    }
}
