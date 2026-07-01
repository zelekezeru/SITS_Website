<?php

use App\Enums\Role;
use App\Models\User;

if (! function_exists('roleBasedRedirect')) {
    function roleBasedRedirect(User $user): string
    {
        return match ($user->primaryRole()) {
            Role::SUPER_ADMIN, Role::CAMPUS_ADMIN => route('dashboard'),
            Role::LIBRARIAN                       => route('circulation.desk'),
            Role::INSTRUCTOR, Role::STAFF_USER    => route('catalog.index'),
            Role::STUDENT                         => route('my.loans'),
            default                               => route('dashboard'),
        };
    }
}
