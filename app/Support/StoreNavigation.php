<?php

namespace App\Support;

use App\Enums\StorePermission;
use App\Models\User;

/**
 * Navigation tree for the Store Keeper portal (Inventory & Asset Management).
 * Mirrors AdminNavigation/FinanceNavigation's shape so the shared AdminLayout
 * sidebar renders it unchanged.
 *
 * Unlike the other trees, every leaf declares the permission it needs and
 * sections() filters against the viewing user — so Operations, Finance and the
 * President each see exactly the store links they can actually open, and nobody
 * is shown a link that would bounce them. Route registration uses the unfiltered
 * modules() list.
 */
class StoreNavigation
{
    /**
     * The Spatie role that owns this portal. Single source of truth for the
     * route gate, RoleLanding::MAP and StorePermissionsSeeder.
     */
    public const ROLE = 'Store Keeper';

    /**
     * @return array<int, array{label:string, items:array<int, array<string,mixed>>}>
     */
    public static function sections(?User $user = null): array
    {
        $sections = [
            [
                'label' => 'Overview',
                'items' => [
                    self::item('Store Dashboard', 'store.dashboard', '/store', 'Warehouse',
                        'Stock health at a glance: reorder alerts, pending requisitions and assets out on loan.',
                        StorePermission::VIEW),
                ],
            ],
            [
                'label' => 'Catalog',
                'items' => [
                    self::item('Items', 'store.items', '/store/items', 'Package',
                        'Every material and asset the Seminary owns, with photos, specifications and reorder policy.',
                        StorePermission::VIEW,
                        ['Consumables & fixed assets', 'Bilingual names (EN/AM)', 'Photos & document attachments', 'Reorder levels']),
                    self::item('Categories', 'store.categories', '/store/categories', 'Layers',
                        'Category tree (e.g. IT → Laptops) carrying tracking-mode and depreciation defaults.',
                        StorePermission::VIEW),
                    self::item('Suppliers', 'store.suppliers', '/store/suppliers', 'Truck',
                        'Vendor register with TIN, contacts and delivery performance.',
                        StorePermission::VIEW),
                    self::item('Store Locations', 'store.locations', '/store/locations', 'MapPin',
                        'Campus → store → room → shelf → bin, to any depth.',
                        StorePermission::VIEW),
                ],
            ],
            [
                'label' => 'Stock',
                'items' => [
                    self::item('Receive Stock (GRN)', 'store.receipts', '/store/receipts', 'PackagePlus',
                        'Goods-received notes: supplier, quantity, unit cost, purchase & production dates, who received it.',
                        StorePermission::RECEIVE,
                        ['Per-batch purchase facts', 'Expiry & warranty tracking', 'Invoice & PO reference', 'Received-by / registered-by audit']),
                    self::item('Issue & Returns', 'store.issues', '/store/issues', 'PackageMinus',
                        'Issue vouchers out to employees and departments, and record returns.',
                        StorePermission::ISSUE),
                    self::item('Requisitions', 'store.requests', '/store/requests', 'ClipboardList',
                        'Staff requests routed for approval before the store releases anything.',
                        StorePermission::VIEW,
                        ['Maker-checker approval', 'Partial fulfilment', 'Per-department history']),
                    self::item('Transfers', 'store.transfers', '/store/transfers', 'ArrowLeftRight',
                        'Move stock and assets between stores, rooms and campuses.',
                        StorePermission::TRANSFER),
                    self::item('Adjustments', 'store.adjustments', '/store/adjustments', 'Sigma',
                        'Post variance corrections — restricted to Operations and the President.',
                        StorePermission::ADJUST),
                ],
            ],
            [
                'label' => 'Assets',
                'items' => [
                    self::item('Asset Register', 'store.assets', '/store/assets', 'Boxes',
                        'Every serialized asset with its tag, condition, location and depreciation schedule.',
                        StorePermission::MANAGE_ASSETS,
                        ['QR-printable asset tags', 'Condition & status', 'Warranty & depreciation', 'Full movement history']),
                    self::item('Custody & Assignments', 'store.assignments', '/store/assignments', 'UserCheck',
                        'Who holds what, since when, due back when — with signed handover slips.',
                        StorePermission::MANAGE_ASSETS),
                    self::item('Maintenance', 'store.maintenance', '/store/maintenance', 'Wrench',
                        'Preventive schedules, repairs, costs and downtime per asset.',
                        StorePermission::MANAGE_ASSETS),
                    self::item('Disposals', 'store.disposals', '/store/disposals', 'Trash2',
                        'Write-off, sale, donation or scrap — requires a second approval.',
                        StorePermission::VIEW),
                ],
            ],
            [
                'label' => 'Control',
                'items' => [
                    self::item('Stocktake', 'store.stocktakes', '/store/stocktakes', 'ClipboardCheck',
                        'Physical count sessions with QR scanning; variances go to Operations to post.',
                        StorePermission::STOCKTAKE),
                    self::item('Reports', 'store.reports', '/store/reports', 'BarChart3',
                        'Stock on hand & valuation, reorder, dead stock, expiry, asset register, custody, consumption.',
                        StorePermission::VIEW_REPORTS),
                ],
            ],
            // The printed-book store. Its own permissions, its own routes, its
            // own tree — appended here so a store role holder reaches it from
            // the portal they already land on, rather than by typing a URL.
            // filterFor() below drops the whole block for a store keeper who
            // holds no bookstore grant.
            ...BookstoreNavigation::sections(),
            ...FinanceNavigation::selfServiceSections(),
        ];

        if ($user === null) {
            return $sections;
        }

        return self::filterFor($sections, $user);
    }

    /**
     * Drop items the user lacks the permission for, then drop sections left
     * empty. Self-service sections carry no permission and always survive.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private static function filterFor(array $sections, User $user): array
    {
        $filtered = [];

        foreach ($sections as $section) {
            $items = array_values(array_filter(
                $section['items'],
                fn (array $item) => ! isset($item['permission']) || $user->can($item['permission'])
            ));

            if ($items !== []) {
                $filtered[] = ['label' => $section['label'], 'items' => $items];
            }
        }

        return $filtered;
    }

    /**
     * Flattened leaves that need a route (the dashboard has its own controller).
     *
     * @return array<int, array<string,mixed>>
     */
    public static function modules(): array
    {
        $flat = [];

        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                // This tree now carries leaves that belong to other modules:
                // self-service links owned by EmployeeNavigation, and bookstore.*
                // links owned by routes/bookstore.php. Both are already routed
                // with their own controllers and gates, so registering them here
                // would silently override both. Only store.* is ours.
                //
                // Note "bookstore." does not start with "store." — the prefixes
                // are distinct, which is what makes this check sufficient.
                if (! str_starts_with($item['name'], 'store.')) {
                    continue;
                }

                $flat[] = [
                    'label' => $item['label'],
                    'name' => $item['name'],
                    'path' => $item['path'],
                    'icon' => $item['icon'] ?? null,
                    'section' => $section['label'],
                    'description' => $item['description'] ?? null,
                    'features' => $item['features'] ?? [],
                    'permission' => $item['permission'] ?? StorePermission::VIEW->value,
                    'children' => [],
                ];
            }
        }

        return array_values(array_filter($flat, fn ($m) => $m['name'] !== 'store.dashboard'));
    }

    public static function module(string $name): ?array
    {
        foreach (self::modules() as $module) {
            if ($module['name'] === $name) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @param  array<int,string>  $features
     * @return array<string, mixed>
     */
    private static function item(
        string $label,
        string $name,
        string $path,
        string $icon,
        string $description,
        StorePermission $permission,
        array $features = [],
    ): array {
        return array_filter([
            'label' => $label,
            'name' => $name,
            'path' => $path,
            'icon' => $icon,
            'description' => $description,
            'features' => $features,
            'permission' => $permission->value,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }
}
