<?php

use App\Models\User;
use App\Support\AdminNavigation;
use App\Support\BookstoreNavigation;
use App\Support\StoreNavigation;
use Database\Seeders\BookstorePermissionsSeeder;
use Database\Seeders\StorePermissionsSeeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/**
 * The bookstore has to be reachable from the sidebar of the people who run it,
 * and invisible to everyone else. These tests pin both halves, plus the hazard
 * that comes with hanging one module's links in another module's tree.
 */
beforeEach(function () {
    // Store first: the bookstore seeder layers onto the Store Keeper role.
    (new StorePermissionsSeeder)->run();
    (new BookstorePermissionsSeeder)->run();

    $this->asRole = function (string $role): User {
        $user = User::factory()->create();
        $user->assignRole(Role::where('name', $role)->firstOrFail());

        return $user;
    };
});

it('points every bookstore nav leaf at a route that actually exists', function () {
    foreach (BookstoreNavigation::leaves() as $leaf) {
        expect(Route::has($leaf['name']))->toBeTrue("Nav leaf [{$leaf['label']}] points at missing route [{$leaf['name']}]");

        // The declared path must be the route's real URI, or the sidebar
        // highlights the wrong item and the link 404s.
        expect('/'.ltrim(Route::getRoutes()->getByName($leaf['name'])->uri(), '/'))
            ->toBe($leaf['path'], "Nav leaf [{$leaf['label']}] declares the wrong path");
    }
});

it('gates every bookstore nav leaf on a permission that exists', function () {
    $known = \Spatie\Permission\Models\Permission::pluck('name')->all();

    foreach (BookstoreNavigation::leaves() as $leaf) {
        expect($leaf['permission'])->toBeIn($known,
            "Nav leaf [{$leaf['label']}] is gated on an unseeded permission");
    }
});

it('shows the store keeper the bookstore in their own sidebar', function () {
    $keeper = ($this->asRole)('Store Keeper');

    $labels = collect(StoreNavigation::sections($keeper))->pluck('label');

    expect($labels)->toContain('Bookstore')
        ->and($labels)->toContain('Bookstore Shelves')
        ->and($labels)->toContain('Book Distribution');
});

it('gives the super admin the bookstore when rolled out on its own', function () {
    // The documented way to add this module to a live database is to run this
    // one seeder alone. That means no LibraryPermissionsSeeder syncing the whole
    // Permission enum to SUPERADMIN — the bookstore grants have to be handed
    // over by name, here, or the super admin ends up locked out of the module.
    foreach (['SUPERADMIN', 'President / Super Admin'] as $name) {
        Role::firstOrCreate(['name' => $name]);
    }

    (new BookstorePermissionsSeeder)->run();

    foreach (['SUPERADMIN', 'President / Super Admin'] as $name) {
        $role = Role::where('name', $name)->firstOrFail();

        expect($role->hasPermissionTo('view_bookstore'))->toBeTrue("[{$name}] cannot see the bookstore")
            ->and($role->hasPermissionTo('approve_book_request'))->toBeTrue("[{$name}] cannot approve a book request")
            ->and($role->hasPermissionTo('approve_payment_bypass'))->toBeTrue("[{$name}] cannot authorise a deferral");
    }
});

it('puts the bookstore in the super admin sidebar', function () {
    $labels = collect(AdminNavigation::sections())->pluck('label');

    expect($labels)->toContain('Bookstore');

    $items = collect(AdminNavigation::sections())->firstWhere('label', 'Bookstore')['items'];
    expect(collect($items)->pluck('name'))->toContain('bookstore.dashboard');
});

it('filters the bookstore sidebar down to what the viewer can open', function () {
    $coordinator = ($this->asRole)('Center Coordinator');

    $names = collect(BookstoreNavigation::sections($coordinator))
        ->pluck('items')->flatten(1)->pluck('name');

    // A coordinator sees the shelves and their requests, but neither the
    // payment desk nor the audit room.
    expect($names)->toContain('bookstore.requests.index')
        ->and($names)->not->toContain('bookstore.payments.index')
        ->and($names)->not->toContain('bookstore.audits.index');
});

it('shows finance the payment desk and hides it from the store', function () {
    $finance = ($this->asRole)('Finance Officer');
    $store   = ($this->asRole)('Store Manager');

    $namesFor = fn (User $u) => collect(BookstoreNavigation::sections($u))
        ->pluck('items')->flatten(1)->pluck('name');

    expect($namesFor($finance))->toContain('bookstore.payments.index')
        ->and($namesFor($store))->not->toContain('bookstore.payments.index')
        ->and($namesFor($store))->toContain('bookstore.dispatches.index');
});

it('shows nothing at all to somebody with no bookstore grant', function () {
    $nobody = User::factory()->create();

    expect(BookstoreNavigation::sections($nobody))->toBe([]);
});

it('never lets a nav tree re-register a bookstore route', function () {
    // StoreNavigation::modules() drives route registration in routes/erp.php.
    // A bookstore leaf leaking into it would override that route's controller
    // and its permission gate with the store module's.
    foreach (StoreNavigation::modules() as $module) {
        expect($module['name'])->toStartWith('store.');
    }

    // Same hazard on the admin side: that loop registers admin.* only.
    $adminOwned = collect(AdminNavigation::modules())
        ->filter(fn ($m) => str_starts_with($m['name'], 'admin.'));

    expect($adminOwned->pluck('name')->filter(fn ($n) => str_contains($n, 'bookstore')))
        ->toBeEmpty();
});

it('reaches the bookstore roles on whatever portal they land on', function () {
    // These roles are not in RoleLanding::MAP, so they land on the employee
    // self-service portal. Binding the bookstore to one nav tree would leave
    // the workflow's own participants unable to see the module they run.
    $bookstoreLinks = function (string $role) {
        $user = ($this->asRole)($role);

        return collect(\App\Support\PortalContext::for($user)['nav'])
            ->pluck('items')->flatten(1)->pluck('name')
            ->filter(fn ($n) => str_starts_with((string) $n, 'bookstore.'));
    };

    expect($bookstoreLinks('Center Coordinator'))->toContain('bookstore.requests.index')
        ->and($bookstoreLinks('Bookstore Approver'))->toContain('bookstore.pipeline')
        ->and($bookstoreLinks('Store Manager'))->toContain('bookstore.dispatches.index')
        // Finance lands on its own portal, which has no bookstore section of its own.
        ->and($bookstoreLinks('Finance Officer'))->toContain('bookstore.payments.index');
});

it('leaves portals alone for anyone with no bookstore grant', function () {
    // No role at all — the self-service portal, which is where the bulk of
    // accounts live and where a stray bookstore link would be noise.
    $employee = User::factory()->create();

    $names = collect(\App\Support\PortalContext::for($employee)['nav'])
        ->pluck('items')->flatten(1)->pluck('name');

    expect($names->filter(fn ($n) => str_starts_with((string) $n, 'bookstore.')))->toBeEmpty()
        ->and($names)->not->toBeEmpty(); // their own portal is untouched
});

it('never shows the same bookstore link twice on one portal', function () {
    // The President's tree and the store keeper's already carry bookstore
    // links; the cross-portal append has to notice that and stay out.
    foreach (['SUPERADMIN', 'President / Super Admin', 'Store Keeper', 'Center Coordinator'] as $role) {
        Role::firstOrCreate(['name' => $role]);
        (new BookstorePermissionsSeeder)->run();

        $names = collect(\App\Support\PortalContext::for(($this->asRole)($role))['nav'])
            ->pluck('items')->flatten(1)->pluck('name')
            ->filter(fn ($n) => str_starts_with((string) $n, 'bookstore.'));

        expect($names->count())->toBe($names->unique()->count(), "[{$role}] sees a duplicated bookstore link");
    }
});

it('keeps the bookstore routes gated after the nav links to them', function () {
    // The sidebar showing a link must never be what grants access.
    $nobody = User::factory()->create();

    $this->actingAs($nobody)->get('/bookstore')->assertForbidden();
    $this->actingAs($nobody)->get('/bookstore/requests')->assertForbidden();
});
