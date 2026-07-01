<?php

namespace App\Enums;

enum Permission: string
{
    // ── Books ──────────────────────────────────────────────────────────────
    case VIEW_BOOKS    = 'view_books';
    case CREATE_BOOK   = 'create_book';
    case EDIT_BOOK     = 'edit_book';
    case DELETE_RECORD = 'delete_record';
    case WITHDRAW_BOOK = 'withdraw_book';

    // ── Circulation ────────────────────────────────────────────────────────
    case CHECKOUT_BOOK         = 'checkout_book';
    case RETURN_BOOK           = 'return_book';
    case OVERRIDE_CAMPUS_CHECK = 'override_campus_check';
    case VIEW_LOANS            = 'view_loans';
    case MANAGE_HOLDS          = 'manage_holds';
    case WAIVE_FINE            = 'waive_fine';
    case COLLECT_FINE          = 'collect_fine';

    // ── Transfers ──────────────────────────────────────────────────────────
    case REQUEST_TRANSFER  = 'request_transfer';
    case APPROVE_TRANSFER  = 'approve_transfer';
    case RECEIVE_TRANSFER  = 'receive_transfer';

    // ── Spatial ────────────────────────────────────────────────────────────
    case MANAGE_CAMPUS    = 'manage_campus';
    case MANAGE_FLOOR     = 'manage_floor';
    case MANAGE_ROW       = 'manage_row';
    case MANAGE_SHELF_BOX = 'manage_shelf_box';

    // ── Digital archive ────────────────────────────────────────────────────
    case UPLOAD_SECURE_PDF    = 'upload_secure_pdf';
    case VIEW_SECURE_PDF      = 'view_secure_pdf';
    case MANAGE_EXTERNAL_LINKS = 'manage_external_links';
    case ACCESS_PREMIUM_RESOURCES = 'access_premium_resources';

    // ── Users ──────────────────────────────────────────────────────────────
    case MANAGE_USERS   = 'manage_users';
    case ASSIGN_ROLES   = 'assign_roles';
    case MANAGE_LEGACY_DATA = 'manage_legacy_data';

    // ── Student portal ─────────────────────────────────────────────────────
    case VIEW_OWN_LOANS = 'view_own_loans';

    public function description(): string
    {
        return match ($this) {
            Permission::VIEW_BOOKS            => 'Browse and search the book catalog',
            Permission::CREATE_BOOK           => 'Add new books to the catalog',
            Permission::EDIT_BOOK             => 'Edit existing book records',
            Permission::DELETE_RECORD         => 'Permanently delete library records',
            Permission::WITHDRAW_BOOK         => 'Mark a book as withdrawn from circulation',
            Permission::CHECKOUT_BOOK         => 'Check out books to borrowers',
            Permission::RETURN_BOOK           => 'Record book returns',
            Permission::OVERRIDE_CAMPUS_CHECK => 'Allow cross-campus borrowing overrides',
            Permission::VIEW_LOANS            => 'View all circulation loans',
            Permission::MANAGE_HOLDS          => 'Manage and override borrower holds',
            Permission::WAIVE_FINE            => 'Waive assessed circulation fines',
            Permission::COLLECT_FINE          => 'Collect payments for circulation fines',
            Permission::REQUEST_TRANSFER      => 'Request inter-branch resource transfers',
            Permission::APPROVE_TRANSFER      => 'Approve inter-branch transfer requests',
            Permission::RECEIVE_TRANSFER      => 'Confirm receipt of transferred resources',
            Permission::MANAGE_CAMPUS         => 'Create and manage campuses',
            Permission::MANAGE_FLOOR          => 'Create and manage floors within a campus',
            Permission::MANAGE_ROW            => 'Create and manage rows within a floor',
            Permission::MANAGE_SHELF_BOX      => 'Create and manage shelf boxes within a row',
            Permission::UPLOAD_SECURE_PDF     => 'Upload restricted PDF documents',
            Permission::VIEW_SECURE_PDF       => 'View restricted PDF documents',
            Permission::MANAGE_EXTERNAL_LINKS => 'Manage external resource links',
            Permission::ACCESS_PREMIUM_RESOURCES => 'Access premium third-party research databases',
            Permission::MANAGE_USERS          => 'Create, edit, and deactivate user accounts',
            Permission::ASSIGN_ROLES          => 'Assign and revoke roles from users',
            Permission::VIEW_OWN_LOANS        => 'View personal borrowing history and active loans',
            Permission::MANAGE_LEGACY_DATA    => 'Import and export legacy library data workbooks',
        };
    }
}
