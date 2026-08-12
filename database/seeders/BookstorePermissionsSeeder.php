<?php

namespace Database\Seeders;

use App\Enums\Permission as BookstorePermission;
use App\Models\StudyMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Bookstore permissions, the roles that hold them, and the study modes the
 * catalogue starts with.
 *
 * The three verification grants are deliberately given to three different roles:
 * whoever checks availability is not whoever checks the money, and neither of
 * them gives final approval.
 */
class BookstorePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        foreach (BookstorePermission::bookstore() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value]);
        }

        $p = fn (BookstorePermission ...$cases) => array_map(fn ($c) => $c->value, $cases);

        // Super admin: everything.
        foreach (['Super Admin', 'President / Super Admin'] as $name) {
            if ($role = Role::where('name', $name)->first()) {
                $role->givePermissionTo($p(...BookstorePermission::bookstore()));
            }
        }

        // Bookstore Admin — runs the catalogue, the stores and the availability check.
        $bookstoreAdmin = Role::firstOrCreate(['name' => 'Bookstore Admin']);
        $bookstoreAdmin->givePermissionTo($p(
            BookstorePermission::VIEW_BOOKSTORE,
            BookstorePermission::MANAGE_BOOK_TITLES,
            BookstorePermission::MANAGE_STORE_ROOMS,
            BookstorePermission::MANAGE_BOOK_STOCK,
            BookstorePermission::MANAGE_PRINT_RUNS,
            BookstorePermission::MANAGE_CENTERS,
            BookstorePermission::CONDUCT_STOCK_AUDIT,
            BookstorePermission::VIEW_BOOK_REPORTS,
        ));

        // Store Manager — holds the keys; dispatches and counts, nothing financial.
        $storeManager = Role::firstOrCreate(['name' => 'Store Manager']);
        $storeManager->givePermissionTo($p(
            BookstorePermission::VIEW_BOOKSTORE,
            // Availability is checked by whoever can see the shelves.
            BookstorePermission::VERIFY_BOOK_REQUEST,
            BookstorePermission::MANAGE_STORE_ROOMS,
            BookstorePermission::MANAGE_BOOK_STOCK,
            BookstorePermission::MANAGE_PRINT_RUNS,
            BookstorePermission::DISPATCH_BOOKS,
            BookstorePermission::RECORD_BOOK_RETURN,
            BookstorePermission::CONDUCT_STOCK_AUDIT,
            BookstorePermission::VIEW_BOOK_REPORTS,
        ));

        // Finance — the money only. No dispatch, no final approval.
        foreach (['Finance Officer', 'Finance Admin'] as $name) {
            $finance = Role::firstOrCreate(['name' => $name]);
            $finance->givePermissionTo($p(
                BookstorePermission::VIEW_BOOKSTORE,
                BookstorePermission::VERIFY_BOOK_PAYMENT,
                // Finance may ask to defer a payment; authorising it is
                // deliberately somebody else's grant.
                BookstorePermission::REQUEST_PAYMENT_BYPASS,
                BookstorePermission::VIEW_BOOK_REPORTS,
            ));
        }

        // Centre coordinators and campus representatives raise and receive requests.
        $coordinator = Role::firstOrCreate(['name' => 'Center Coordinator']);
        $coordinator->givePermissionTo($p(
            BookstorePermission::VIEW_BOOKSTORE,
            BookstorePermission::REQUEST_BOOKS,
            BookstorePermission::RECEIVE_BOOKS,
        ));

        // Operational Manager — final approval and the variance sign-off.
        if ($ops = Role::where('name', 'Operational Manager')->first()) {
            $ops->givePermissionTo($p(
                BookstorePermission::VIEW_BOOKSTORE,
                BookstorePermission::APPROVE_BOOK_REQUEST,
                BookstorePermission::APPROVE_PAYMENT_BYPASS,
                BookstorePermission::APPROVE_STOCK_AUDIT,
                BookstorePermission::VIEW_BOOK_REPORTS,
            ));
        }

        $this->seedStudyModes();
    }

    /** The modes already in use; an admin can add more without a deploy. */
    protected function seedStudyModes(): void
    {
        $modes = [
            ['name' => 'Regular',  'code' => 'REG', 'sort_order' => 1],
            ['name' => 'Distance', 'code' => 'DST', 'sort_order' => 2],
            ['name' => 'Evening',  'code' => 'EVE', 'sort_order' => 3],
            ['name' => 'Weekend',  'code' => 'WKD', 'sort_order' => 4],
            ['name' => 'Online',   'code' => 'ONL', 'sort_order' => 5],
        ];

        foreach ($modes as $mode) {
            StudyMode::firstOrCreate(['code' => $mode['code']], $mode);
        }
    }
}
