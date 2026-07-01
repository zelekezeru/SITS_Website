<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

use App\Traits\LogsOperationalActivity;

class ExternalResource extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'name',
        'url',
        'category',
        'provider',
        'description',
        'logo_path',
        'access_tier',
        'required_permission',
        'allowed_roles',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'allowed_roles' => 'array',
        'is_active' => 'boolean',
    ];

    public function isVisibleTo(?User $user): bool
    {
        if (!$this->is_active) return false;
        if ($this->access_tier === 'free') return true;
        if (!$user) return false;
        if ($user->hasRole('super_admin')) return true;

        if ($this->required_permission && $user->can($this->required_permission)) return true;
        
        if (!empty($this->allowed_roles) && $user->roles->pluck('name')->intersect($this->allowed_roles)->isNotEmpty()) {
            return true;
        }

        return false;
    }

    public function scopeVisibleTo($q, ?User $user)
    {
        return $q->where('is_active', true)->where(function ($w) use ($user) {
            $w->where('access_tier', 'free');
            if ($user) {
                if ($user->hasRole('super_admin')) {
                    $w->orWhereRaw('1=1');
                    return;
                }

                $perms = $user->getAllPermissions()->pluck('name');
                $roles = $user->roles->pluck('name');
                
                $w->orWhereIn('required_permission', $perms);
                
                if ($roles->isNotEmpty()) {
                    foreach ($roles as $r) {
                        $w->orWhereJsonContains('allowed_roles', $r);
                    }
                }
            }
        });
    }
}
