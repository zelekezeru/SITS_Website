<?php

namespace Database\Seeders;

use App\Enums\StorePermission;
use App\Support\StoreNavigation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Inventory & Asset Management ("Store") access control.
 *
 * Kept as its own seeder — rather than folded into RolesAndPermissionsSeeder —
 * because it *adds to* existing roles with givePermissionTo() instead of
 * syncPermissions(). Running it after RolesAndPermissionsSeeder therefore layers
 * store access on without disturbing the payroll/performance grants, and it can
 * be re-run alone on production to roll the module out.
 *
 * Permission matrix and the reasoning behind each denial:
 * docs/inventory-management-design.md §3.
 */
class StorePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        foreach (StorePermission::names() as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // ── The custodian ──────────────────────────────────────────────────
        // Runs the store day to day: catalog, receiving, issuing, transfers,
        // the asset register and physical counts. Deliberately cannot approve
        // requisitions, post adjustments, or approve disposals.
        Role::firstOrCreate(['name' => StoreNavigation::ROLE])
            ->syncPermissions(StorePermission::storeKeeper());

        // ── The checkers ───────────────────────────────────────────────────
        // President already holds every permission via RolesAndPermissionsSeeder's
        // syncPermissions($all), but that runs before these rows exist.
        $this->grant('President / Super Admin', StorePermission::names());

        $this->grant('Operational Manager', [
            ...StorePermission::oversight(),
            StorePermission::VIEW->value,
            StorePermission::VIEW_REPORTS->value,
            StorePermission::MANAGE_CATALOG->value,
            StorePermission::MANAGE_LOCATIONS->value,
            StorePermission::MANAGE_SUPPLIERS->value,
            StorePermission::RECEIVE->value,
            StorePermission::TRANSFER->value,
            StorePermission::MANAGE_ASSETS->value,
            StorePermission::STOCKTAKE->value,
            StorePermission::REQUEST->value,
            StorePermission::VIEW_OWN_REQUESTS->value,
        ]);

        $this->grant('Vice President', [
            StorePermission::VIEW->value,
            StorePermission::VIEW_REPORTS->value,
            StorePermission::APPROVE_REQUESTS->value,
            StorePermission::APPROVE_DISPOSAL->value,
            StorePermission::REQUEST->value,
            StorePermission::VIEW_OWN_REQUESTS->value,
        ]);

        // Finance needs valuation and consumption cost data — not stock control.
        $this->grant('Finance Officer', [
            StorePermission::VIEW->value,
            StorePermission::VIEW_REPORTS->value,
            StorePermission::REQUEST->value,
            StorePermission::VIEW_OWN_REQUESTS->value,
        ]);

        // Department heads authorise their team's requisitions.
        $this->grant('Department Head', [
            StorePermission::VIEW->value,
            StorePermission::APPROVE_REQUESTS->value,
            StorePermission::REQUEST->value,
            StorePermission::VIEW_OWN_REQUESTS->value,
        ]);

        $this->grant('Dean of the Seminary', [
            StorePermission::VIEW->value,
            StorePermission::APPROVE_REQUESTS->value,
            StorePermission::REQUEST->value,
            StorePermission::VIEW_OWN_REQUESTS->value,
        ]);

        // Everyone with an ERP account can ask the store for something.
        foreach (['Registrar', 'Employee'] as $role) {
            $this->grant($role, [
                StorePermission::REQUEST->value,
                StorePermission::VIEW_OWN_REQUESTS->value,
            ]);
        }
    }

    /**
     * Add permissions to a role without clearing what it already has. Skips
     * silently if the role hasn't been seeded (e.g. a partial local database).
     *
     * @param  array<int, string>  $permissions
     */
    private function grant(string $roleName, array $permissions): void
    {
        /** @var Role|null $role */
        $role = Role::query()->where('name', $roleName)->first();

        $role?->givePermissionTo(array_unique($permissions));
    }
}
