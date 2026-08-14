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

    // ── Bookstore: catalogue & locations ───────────────────────────────────
    case VIEW_BOOKSTORE       = 'view_bookstore';
    case MANAGE_BOOK_TITLES   = 'manage_book_titles';
    case MANAGE_STORE_ROOMS   = 'manage_store_rooms';
    case MANAGE_BOOK_STOCK    = 'manage_book_stock';
    case MANAGE_PRINT_RUNS    = 'manage_print_runs';
    case MANAGE_CENTERS       = 'manage_centers';

    // ── Bookstore: request workflow (segregated on purpose) ────────────────
    case REQUEST_BOOKS        = 'request_books';
    case VERIFY_BOOK_REQUEST  = 'verify_book_request';
    case VERIFY_BOOK_PAYMENT  = 'verify_book_payment';
    case APPROVE_BOOK_REQUEST = 'approve_book_request';
    case DISPATCH_BOOKS       = 'dispatch_books';
    case RECEIVE_BOOKS        = 'receive_books';
    case RECORD_BOOK_RETURN   = 'record_book_return';

    // ── Bookstore: pay-later deferrals ─────────────────────────────────────
    // Raising a deferral and authorising one are separate grants on purpose:
    // Finance asks, somebody else accepts the debt.
    case REQUEST_PAYMENT_BYPASS = 'request_payment_bypass';
    case APPROVE_PAYMENT_BYPASS = 'approve_payment_bypass';

    // ── Bookstore: audit & reporting ───────────────────────────────────────
    case CONDUCT_STOCK_AUDIT  = 'conduct_stock_audit';
    case APPROVE_STOCK_AUDIT  = 'approve_stock_audit';
    case VIEW_BOOK_REPORTS    = 'view_book_reports';

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
            Permission::VIEW_BOOKSTORE        => 'View the printed-book store, stock levels and locations',
            Permission::MANAGE_BOOK_TITLES    => 'Create and edit printed book titles and their categories',
            Permission::MANAGE_STORE_ROOMS    => 'Manage store rooms, shelves and shelf sections',
            Permission::MANAGE_BOOK_STOCK     => 'Post stock transfers and adjustments, and receive low-stock alerts',
            Permission::MANAGE_PRINT_RUNS     => 'Record printing batches received into the store',
            Permission::MANAGE_CENTERS        => 'Manage distribution centres and their coordinators',
            Permission::REQUEST_BOOKS         => 'Raise a book request for a centre or campus',
            Permission::VERIFY_BOOK_REQUEST   => 'Verify a request for availability and genuineness, reserving stock',
            Permission::VERIFY_BOOK_PAYMENT   => 'Verify the payment attached to a book request',
            Permission::APPROVE_BOOK_REQUEST  => 'Give final approval to a verified and paid book request',
            Permission::DISPATCH_BOOKS        => 'Dispatch approved books out of the store',
            Permission::RECEIVE_BOOKS         => 'Confirm receipt of a dispatched consignment',
            Permission::RECORD_BOOK_RETURN    => 'Record books returned from a centre or campus',
            Permission::REQUEST_PAYMENT_BYPASS => 'Ask for a book request to proceed before payment is received',
            Permission::APPROVE_PAYMENT_BYPASS => 'Authorise a pay-later deferral, accepting the outstanding debt',
            Permission::CONDUCT_STOCK_AUDIT   => 'Start and record physical stock counts',
            Permission::APPROVE_STOCK_AUDIT   => 'Approve counted variances and post the corrections',
            Permission::VIEW_BOOK_REPORTS     => 'View and export bookstore reports',
        };
    }

    /** The bookstore subset — handy for seeding and for role screens. */
    public static function bookstore(): array
    {
        return [
            self::VIEW_BOOKSTORE,
            self::MANAGE_BOOK_TITLES,
            self::MANAGE_STORE_ROOMS,
            self::MANAGE_BOOK_STOCK,
            self::MANAGE_PRINT_RUNS,
            self::MANAGE_CENTERS,
            self::REQUEST_BOOKS,
            self::VERIFY_BOOK_REQUEST,
            self::VERIFY_BOOK_PAYMENT,
            self::APPROVE_BOOK_REQUEST,
            self::DISPATCH_BOOKS,
            self::RECEIVE_BOOKS,
            self::RECORD_BOOK_RETURN,
            self::REQUEST_PAYMENT_BYPASS,
            self::APPROVE_PAYMENT_BYPASS,
            self::CONDUCT_STOCK_AUDIT,
            self::APPROVE_STOCK_AUDIT,
            self::VIEW_BOOK_REPORTS,
        ];
    }
}
