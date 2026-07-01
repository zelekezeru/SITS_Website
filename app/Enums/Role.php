<?php

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN  = 'super_admin';
    case CAMPUS_ADMIN = 'campus_admin';
    case LIBRARIAN    = 'librarian';
    case INSTRUCTOR   = 'instructor';
    case STAFF_USER   = 'staff_user';
    case STUDENT      = 'student';

    public function label(): string
    {
        return match ($this) {
            Role::SUPER_ADMIN  => 'Super Admin',
            Role::CAMPUS_ADMIN => 'Campus Admin',
            Role::LIBRARIAN    => 'Librarian',
            Role::INSTRUCTOR   => 'Instructor',
            Role::STAFF_USER   => 'Staff / User',
            Role::STUDENT      => 'Student',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            Role::SUPER_ADMIN  => 'purple',
            Role::CAMPUS_ADMIN => 'indigo',
            Role::LIBRARIAN    => 'blue',
            Role::INSTRUCTOR   => 'green',
            Role::STAFF_USER   => 'gray',
            Role::STUDENT      => 'amber',
        };
    }
}
