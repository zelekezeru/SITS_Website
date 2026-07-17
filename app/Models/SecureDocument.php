<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\LogsOperationalActivity;

class SecureDocument extends Model
{
    use HasFactory, LogsOperationalActivity, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'disk',
        'path',
        'original_filename',
        'size_bytes',
        'mime',
        'sha256',
        'book_id',
        'uploaded_by',
        'visibility',
        'watermark_user',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(SecureDocumentAccess::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(SecureDocumentView::class);
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($user->hasAnyRole(['SUPERADMIN', 'ADMIN', 'President / Super Admin'])) return true;
        if (!$user->can('view_secure_pdf')) return false;

        return match ($this->visibility) {
            'public_authenticated' => true,
            'role_gated'           => true, // Permission checked above
            'document_gated'       => $this->grants()
                ->where(function ($q) use ($user) {
                    // Grant to the specific user
                    $q->where(function ($w) use ($user) {
                        $w->where('grantee_type', User::class)
                          ->where('grantee_id', $user->id);
                    })
                    // OR grant to any of the user's roles
                    ->orWhere(function ($w) use ($user) {
                        $w->where('grantee_type', \Spatie\Permission\Models\Role::class)
                          ->whereIn('grantee_id', $user->roles->pluck('id'));
                    });
                })
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists(),
            default => false,
        };
    }
}
