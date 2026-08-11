<?php

namespace App\Enums;

/**
 * Permissions for the Inventory & Asset Management ("Store") module.
 *
 * ERP permissions are plain space-separated Spatie permission names (unlike the
 * library's snake_case set in App\Enums\Permission). Enumerating them here keeps
 * the seeder, route middleware and navigation from hardcoding string literals.
 *
 * Segregation of duties is intentional: the Store Keeper holds the goods but may
 * not authorise their release (APPROVE_REQUESTS), post a stocktake variance to
 * stock (ADJUST) or write an asset off (APPROVE_DISPOSAL). Those sit with
 * Operations / the President. See docs/inventory-management-design.md §3.
 */
enum StorePermission: string
{
    // ── Read ───────────────────────────────────────────────────────────────
    case VIEW = 'view inventory';
    case VIEW_REPORTS = 'view inventory reports';

    // ── Catalog & reference data ───────────────────────────────────────────
    case MANAGE_CATALOG = 'manage inventory catalog';
    case MANAGE_LOCATIONS = 'manage inventory locations';
    case MANAGE_SUPPLIERS = 'manage inventory suppliers';

    // ── Stock movement ─────────────────────────────────────────────────────
    case RECEIVE = 'receive inventory';
    case ISSUE = 'issue inventory';
    case TRANSFER = 'transfer inventory';
    case ADJUST = 'adjust inventory';

    // ── Assets ─────────────────────────────────────────────────────────────
    case MANAGE_ASSETS = 'manage inventory assets';
    case APPROVE_DISPOSAL = 'approve inventory disposal';

    // ── Requisition workflow ───────────────────────────────────────────────
    case REQUEST = 'request inventory';
    case VIEW_OWN_REQUESTS = 'view own inventory requests';
    case APPROVE_REQUESTS = 'approve inventory requests';

    // ── Control ────────────────────────────────────────────────────────────
    case STOCKTAKE = 'conduct inventory stocktake';

    public function description(): string
    {
        return match ($this) {
            self::VIEW              => 'Browse the inventory catalog, stock levels and asset register',
            self::VIEW_REPORTS      => 'View and export inventory reports and valuations',
            self::MANAGE_CATALOG    => 'Create and edit inventory items and categories',
            self::MANAGE_LOCATIONS  => 'Create and edit stores, rooms, shelves and bins',
            self::MANAGE_SUPPLIERS  => 'Create and edit supplier records',
            self::RECEIVE           => 'Record goods received (GRN) into store',
            self::ISSUE             => 'Issue stock and assets out to employees and departments',
            self::TRANSFER          => 'Move stock and assets between locations',
            self::ADJUST            => 'Post stock adjustments and stocktake variances',
            self::MANAGE_ASSETS     => 'Manage the asset register, custody assignments and maintenance',
            self::APPROVE_DISPOSAL  => 'Approve write-off, sale or donation of assets',
            self::REQUEST           => 'Raise an inventory requisition',
            self::VIEW_OWN_REQUESTS => 'View personal requisitions and items currently held',
            self::APPROVE_REQUESTS  => 'Approve or reject inventory requisitions',
            self::STOCKTAKE         => 'Run physical stock counts and record variances',
        };
    }

    /** @return array<int, string> every permission name, for seeding. */
    public static function names(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Permissions granted to the Store Keeper role.
     *
     * @return array<int, string>
     */
    public static function storeKeeper(): array
    {
        return array_column([
            self::VIEW, self::VIEW_REPORTS,
            self::MANAGE_CATALOG, self::MANAGE_LOCATIONS, self::MANAGE_SUPPLIERS,
            self::RECEIVE, self::ISSUE, self::TRANSFER,
            self::MANAGE_ASSETS,
            self::REQUEST, self::VIEW_OWN_REQUESTS,
            self::STOCKTAKE,
        ], 'value');
    }

    /**
     * Oversight permissions — the checker half of the store's maker-checker
     * controls. Held by Operations and the President, never by the custodian.
     *
     * @return array<int, string>
     */
    public static function oversight(): array
    {
        return array_column([
            self::APPROVE_REQUESTS, self::ADJUST, self::APPROVE_DISPOSAL,
        ], 'value');
    }
}
