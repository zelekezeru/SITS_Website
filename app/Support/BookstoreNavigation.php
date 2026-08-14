<?php

namespace App\Support;

use App\Enums\Permission as BookstorePermission;
use App\Models\User;

/**
 * Navigation tree for the printed-book store.
 *
 * Shaped like StoreNavigation so the shared AdminLayout sidebar renders it
 * unchanged, and — as there — every leaf declares the permission it needs and
 * sections() filters against the viewing user. A coordinator who can only raise
 * requests sees the request links and nothing else; a store manager sees the
 * shelves and the dispatch desk but no payment screen.
 *
 * Unlike StoreNavigation there is deliberately **no** modules() route-registration
 * helper. routes/bookstore.php owns every bookstore route with its own controller
 * and permission gate; this class only ever produces *links* to routes that
 * already exist. Anything that loops over a nav tree to register routes must skip
 * these leaves — see the guards in routes/erp.php.
 */
class BookstoreNavigation
{
    /**
     * The tree, filtered to what $user may actually open. Passing null returns
     * it unfiltered (useful for tests and for enumerating every leaf).
     *
     * @return array<int, array{label:string, items:array<int, array<string,mixed>>}>
     */
    public static function sections(?User $user = null): array
    {
        $sections = [
            [
                'label' => 'Bookstore',
                'items' => [
                    self::item('Book Store', 'bookstore.dashboard', '/bookstore', 'BookOpen',
                        'Printed course books: stock on hand, requests in flight and what is running low.',
                        BookstorePermission::VIEW_BOOKSTORE,
                        ['Stock by title and location', 'Requests awaiting your action', 'Low-stock alerts', 'Dispatches in transit']),
                    self::item('Approval Pipeline', 'bookstore.pipeline', '/bookstore/pipeline', 'ClipboardList',
                        'Every open request, who owes the next action by name, and how long it has waited.',
                        BookstorePermission::VIEW_BOOKSTORE,
                        ['Grouped by the stage that owes an action', 'Dwell time per stage', 'Stalled requests flagged']),
                ],
            ],
            [
                'label' => 'Bookstore Catalogue',
                'items' => [
                    self::item('Book Titles', 'bookstore.titles.index', '/bookstore/titles', 'BookText',
                        'Every printed title with its course code, programme, language and study mode.',
                        BookstorePermission::VIEW_BOOKSTORE,
                        ['Course code & name as printed', 'Programme, language, study mode', 'Unit price & reorder level', 'Printable QR label']),
                    self::item('Print Runs', 'bookstore.print-runs.index', '/bookstore/print-runs', 'Printer',
                        'Printing batches received into the store, and the stock they produced.',
                        BookstorePermission::MANAGE_PRINT_RUNS),
                    self::item('Centres & Campuses', 'bookstore.centers.index', '/bookstore/centers', 'Building2',
                        'The centres and campuses books are distributed to, with their verified student counts.',
                        BookstorePermission::VIEW_BOOKSTORE),
                ],
            ],
            [
                'label' => 'Bookstore Shelves',
                'items' => [
                    self::item('Store Rooms', 'bookstore.stores.index', '/bookstore/stores', 'Warehouse',
                        'Store room → shelf → section, each QR-labelled so a scan lands on the exact shelf.',
                        BookstorePermission::VIEW_BOOKSTORE,
                        ['QR label per room, shelf and section', 'Stock held per section', 'Picking map for dispatch']),
                    self::item('Stock on Hand', 'bookstore.stock.index', '/bookstore/stock', 'Boxes',
                        'Quantity per title per section, with the movement ledger behind every number.',
                        BookstorePermission::VIEW_BOOKSTORE),
                    self::item('Low Stock', 'bookstore.stock.low', '/bookstore/stock/low', 'AlertTriangle',
                        'Titles at or below their reorder level — what to print next.',
                        BookstorePermission::VIEW_BOOKSTORE),
                    self::item('Scan a Label', 'bookstore.scan.index', '/bookstore/scan', 'ScanLine',
                        'Point a phone at any bookstore QR code to open the record it belongs to.',
                        BookstorePermission::VIEW_BOOKSTORE),
                    self::item('Print Labels', 'bookstore.labels.sheet', '/bookstore/labels/sheet', 'QrCode',
                        'Lay QR labels out on a sheet for printing, each with its name underneath.',
                        BookstorePermission::VIEW_BOOKSTORE),
                ],
            ],
            [
                'label' => 'Book Requests',
                'items' => [
                    self::item('Book Requests', 'bookstore.requests.index', '/bookstore/requests', 'ClipboardCheck',
                        'Requests from centres and campuses, from first submission through to confirmed receipt.',
                        BookstorePermission::VIEW_BOOKSTORE,
                        ['Availability verified before payment', 'Stock reserved, not yet deducted', 'Full approval trail with timestamps']),
                    self::item('Payments', 'bookstore.payments.index', '/bookstore/payments', 'Receipt',
                        'Bank transfers and cash against each request — reference, CRV number and receipt image.',
                        BookstorePermission::VERIFY_BOOK_PAYMENT,
                        ['Transaction reference', 'Manual CRV number', 'Receipt image attached', 'Verify or reject with a reason']),
                    self::item('Deferred Payments', 'bookstore.bypasses.index', '/bookstore/bypasses', 'HandCoins',
                        'Pay-later deferrals: who asked, who authorised, and what is still owed.',
                        BookstorePermission::VIEW_BOOKSTORE,
                        ['Written reason required to raise', 'Written justification required to approve', 'Never the same person for both', 'Debt stays visible until settled']),
                ],
            ],
            [
                'label' => 'Book Distribution',
                'items' => [
                    self::item('Dispatches', 'bookstore.dispatches.index', '/bookstore/dispatches', 'Truck',
                        'Waybills for books leaving the store, printable in the handover-form layout.',
                        BookstorePermission::VIEW_BOOKSTORE,
                        ['Picked from a named shelf section', 'QR-stamped waybill PDF', 'Receipt confirmed by the receiver']),
                    self::item('Returns', 'bookstore.returns.index', '/bookstore/returns', 'Undo2',
                        'Books coming back from a centre or campus, back onto a shelf section.',
                        BookstorePermission::VIEW_BOOKSTORE),
                ],
            ],
            [
                'label' => 'Bookstore Control',
                'items' => [
                    self::item('Stock Audits', 'bookstore.audits.index', '/bookstore/audits', 'ClipboardCheck',
                        'Blind physical counts with a variance report; corrections post only on approval.',
                        BookstorePermission::CONDUCT_STOCK_AUDIT,
                        ['Counted without showing the expected figure', 'Variance per title per section', 'Approval before any correction posts']),
                    self::item('Bookstore Reports', 'bookstore.reports.index', '/bookstore/reports', 'BarChart3',
                        'Stock, distribution, payments, outstanding debt and per-stage approval lag.',
                        BookstorePermission::VIEW_BOOK_REPORTS,
                        ['Stock & movement history', 'Distribution per centre', 'Outstanding payments', 'Where the approval lag is']),
                ],
            ],
        ];

        return $user === null ? $sections : self::filterFor($sections, $user);
    }

    /**
     * Every leaf, flattened — for tests and for anything that needs to check the
     * tree against the route table.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function leaves(): array
    {
        $flat = [];

        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                $flat[] = $item + ['section' => $section['label']];
            }
        }

        return $flat;
    }

    /**
     * Drop leaves the user lacks the permission for, then drop sections left
     * empty — so nobody is shown a link that would bounce them.
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
                fn (array $item) => $user->can($item['permission'])
            ));

            if ($items !== []) {
                $filtered[] = ['label' => $section['label'], 'items' => $items];
            }
        }

        return $filtered;
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
        BookstorePermission $permission,
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
