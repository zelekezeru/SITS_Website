<?php

namespace Database\Seeders;

use App\Enums\Permission as LibPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * SITS Library merge — RBAC unification.
 *
 * Creates the library's granular permissions and grants them to the EXISTING
 * unified SITS roles (the "unify into SITS scheme" decision), instead of adding
 * the library's own role names. super_admin→SUPERADMIN, librarian→LIBRARIAN,
 * instructor→TRAINER, staff_user→STAFF, student→STUDENT, campus_admin→ADMIN.
 */
class LibraryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create every library permission.
        foreach (LibPermission::cases() as $p) {
            Permission::firstOrCreate(['name' => $p->value, 'guard_name' => 'web']);
        }
        $all = array_map(fn (LibPermission $p) => $p->value, LibPermission::cases());

        // 2. Permission sets (mirrors sits-library's RolesAndPermissionsSeeder).
        $librarian = [
            'view_books', 'create_book', 'edit_book', 'withdraw_book', 'delete_record',
            'checkout_book', 'return_book', 'view_loans', 'manage_holds', 'waive_fine', 'collect_fine',
            'request_transfer', 'approve_transfer', 'receive_transfer',
            'upload_secure_pdf', 'view_secure_pdf', 'access_premium_resources',
            'manage_campus', 'manage_floor', 'manage_row', 'manage_shelf_box',
            'manage_external_links', 'manage_legacy_data', 'view_own_loans',
        ];
        $editor     = ['view_books', 'create_book', 'edit_book', 'withdraw_book', 'manage_external_links', 'view_secure_pdf'];
        $instructor = ['view_books', 'checkout_book', 'return_book', 'view_secure_pdf', 'access_premium_resources', 'view_own_loans'];
        $staff      = ['view_books', 'checkout_book', 'return_book', 'view_secure_pdf', 'view_own_loans'];
        $student    = ['view_books', 'checkout_book', 'return_book', 'view_own_loans'];
        $basic      = ['view_books', 'view_own_loans'];

        // 3. Map onto existing SITS roles (skip roles that don't exist).
        $map = [
            'SUPERADMIN'               => $all,
            'President / Super Admin'  => $all,
            'ADMIN'                    => $all,
            'LIBRARIAN'                => $librarian,
            'EDITOR'                   => $editor,
            'TRAINER'                  => $instructor,
            'STAFF'                    => $staff,
            'STUDENT'                  => $student,
            'USER'                     => $basic,
        ];

        foreach ($map as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('✓ Library permissions created and mapped onto SITS roles.');
    }
}
