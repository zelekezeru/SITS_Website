<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\IntegrityDocument;
use App\Models\User;

/**
 * Ownership-only: an instructor sees documents they created; Admin sees
 * all. No course-based access — Course.instructor is a free-text column,
 * not linked to a user account, so it can't back an authorization check.
 */
class IntegrityDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access-integrity-suite');
    }

    public function view(User $user, IntegrityDocument $document): bool
    {
        return $this->isAdmin($user) || $document->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('access-integrity-suite');
    }

    public function update(User $user, IntegrityDocument $document): bool
    {
        return $this->view($user, $document);
    }

    protected function isAdmin(User $user): bool
    {
        return in_array($user->primaryRole(), [Role::SUPER_ADMIN, Role::CAMPUS_ADMIN], true);
    }
}
