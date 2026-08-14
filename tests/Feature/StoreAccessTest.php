<?php

use App\Enums\StorePermission;
use App\Models\User;
use App\Support\RoleLanding;
use App\Support\StoreNavigation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StorePermissionsSeeder;

/**
 * Access control for the Inventory & Asset Management ("Store") module.
 *
 * The permission matrix and the reasoning behind each denial live in
 * docs/inventory-management-design.md §3 — these tests are what keep the code
 * honest about it.
 */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(StorePermissionsSeeder::class);
});

/** A signed-in, approved, active user past the forced-password-change gate. */
function storeUser(?string $role = null): User
{
    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password_changed' => true,
    ]);

    if ($role) {
        $user->assignRole($role);
    }

    return $user;
}

// ---- Role & landing --------------------------------------------------------

it('seeds the Store Keeper role with exactly its intended permissions', function () {
    $role = Spatie\Permission\Models\Role::where('name', StoreNavigation::ROLE)->first();

    expect($role)->not->toBeNull()
        ->and($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect(StorePermission::storeKeeper())->sort()->values()->all());
});

it('lands a store keeper on the store dashboard', function () {
    $user = storeUser(StoreNavigation::ROLE);

    expect(RoleLanding::routeName($user))->toBe('store.dashboard');

    $this->actingAs($user)->get('/store')->assertOk();
});

it('shares the store sidebar and portal chip with a store keeper', function () {
    $this->actingAs(storeUser(StoreNavigation::ROLE))
        ->get('/store')
        ->assertInertia(fn ($page) => $page
            ->component('Store/Dashboard')
            ->where('portal.home', '/store')
            ->where('portal.roleLabel', 'Store · Inventory'));
});

// ---- Segregation of duties -------------------------------------------------

it('withholds approval, adjustment and disposal from the store keeper', function () {
    $keeper = storeUser(StoreNavigation::ROLE);

    // Holds the goods…
    expect($keeper->can(StorePermission::RECEIVE->value))->toBeTrue()
        ->and($keeper->can(StorePermission::ISSUE->value))->toBeTrue()
        ->and($keeper->can(StorePermission::STOCKTAKE->value))->toBeTrue()
        // …but cannot authorise their release, erase a variance, or write one off.
        ->and($keeper->can(StorePermission::APPROVE_REQUESTS->value))->toBeFalse()
        ->and($keeper->can(StorePermission::ADJUST->value))->toBeFalse()
        ->and($keeper->can(StorePermission::APPROVE_DISPOSAL->value))->toBeFalse();
});

it('blocks the store keeper from the adjustments page', function () {
    $this->actingAs(storeUser(StoreNavigation::ROLE))
        ->get('/store/adjustments')
        ->assertForbidden();
});

it('lets Operations post adjustments but not issue stock', function () {
    $ops = storeUser('Operational Manager');

    expect($ops->can(StorePermission::ADJUST->value))->toBeTrue()
        ->and($ops->can(StorePermission::APPROVE_REQUESTS->value))->toBeTrue()
        ->and($ops->can(StorePermission::ISSUE->value))->toBeFalse();

    $this->actingAs($ops)->get('/store/adjustments')->assertOk();
    $this->actingAs($ops)->get('/store/issues')->assertForbidden();
});

it('gives Finance read access only', function () {
    $finance = storeUser('Finance Officer');

    expect($finance->can(StorePermission::VIEW->value))->toBeTrue()
        ->and($finance->can(StorePermission::VIEW_REPORTS->value))->toBeTrue()
        ->and($finance->can(StorePermission::RECEIVE->value))->toBeFalse()
        ->and($finance->can(StorePermission::MANAGE_CATALOG->value))->toBeFalse();

    $this->actingAs($finance)->get('/store/items')->assertOk();
    $this->actingAs($finance)->get('/store/receipts')->assertForbidden();
});

it('lets any employee raise a requisition but not open the store', function () {
    $employee = storeUser('Employee');

    expect($employee->can(StorePermission::REQUEST->value))->toBeTrue()
        ->and($employee->can(StorePermission::VIEW->value))->toBeFalse();

    $this->actingAs($employee)->get('/store/items')->assertForbidden();
});

it('bounces a non-store user off the store landing instead of 403ing', function () {
    // EnsureRole redirects to the user's own dashboard — a hard 403 on a landing
    // route is jarring under Inertia.
    $employee = storeUser('Employee');

    $this->actingAs($employee)
        ->get('/store')
        ->assertRedirect(RoleLanding::url($employee));
});

it('lets the President reach every store page', function () {
    $president = storeUser('President / Super Admin');

    $this->actingAs($president)->get('/store')->assertOk();

    foreach (StoreNavigation::modules() as $module) {
        $this->actingAs($president)->get($module['path'])->assertOk();
    }
});

// ---- Route surface & navigation --------------------------------------------

it('registers every store nav leaf as a real permission-gated route', function () {
    foreach (StoreNavigation::modules() as $module) {
        expect(Route::has($module['name']))->toBeTrue("missing route {$module['name']}");

        $middleware = Route::getRoutes()->getByName($module['name'])->gatherMiddleware();

        expect($middleware)->toContain('can:'.$module['permission']);
    }
});

it('keeps the admin nav loop from re-registering store routes', function () {
    // AdminNavigation links to /store/* rather than duplicating the pages; a
    // second registration would silently override the controller + can: gate.
    $storeRoute = Route::getRoutes()->getByName('store.assets');

    expect($storeRoute->getActionName())
        ->toBe(App\Http\Controllers\Store\ModuleController::class);
});

it('filters the store sidebar to what the viewer can actually open', function () {
    $keeperLinks = collect(StoreNavigation::sections(storeUser(StoreNavigation::ROLE)))
        ->flatMap(fn ($s) => collect($s['items'])->pluck('name'))->all();

    $financeLinks = collect(StoreNavigation::sections(storeUser('Finance Officer')))
        ->flatMap(fn ($s) => collect($s['items'])->pluck('name'))->all();

    expect($keeperLinks)->toContain('store.receipts')
        ->and($keeperLinks)->not->toContain('store.adjustments')
        ->and($financeLinks)->toContain('store.reports')
        ->and($financeLinks)->not->toContain('store.receipts');
});
