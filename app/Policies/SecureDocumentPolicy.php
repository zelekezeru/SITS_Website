<?php

namespace App\Policies;

use App\Models\SecureDocument;
use App\Models\User;

/**
 * Digital Archive access: viewing is gated by "view_secure_pdf" (plus the
 * document's own visibility rules), while uploading and deleting are gated by
 * "upload_secure_pdf" — both held by LIBRARIAN and the admin roles.
 * President / Super Admin bypasses everything via Gate::before.
 */
class SecureDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_secure_pdf');
    }

    public function view(User $user, SecureDocument $document): bool
    {
        return $document->isAccessibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can('upload_secure_pdf');
    }

    public function delete(User $user, SecureDocument $document): bool
    {
        return $user->can('upload_secure_pdf');
    }
}
