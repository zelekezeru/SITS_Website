<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

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
        if ($this->hasAnyRole(['SUPERADMIN', 'ADMIN', 'LIBRARIAN'])) {
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
}
