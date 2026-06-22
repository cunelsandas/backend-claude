<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Ports the legacy `config/permissions.php` ACL into Spatie's DB-driven model.
 *
 * Strategy:
 *  - 1 Role per legacy group (super-admin, production-operator, production-maintainer, accountant)
 *  - 1 Permission per route name in each group's `allowed_routes`
 *  - Wildcard permissions (e.g. "product.*") stored verbatim — checks must
 *    handle the wildcard via a Gate::before or custom can() helper.
 *  - super-admin role gets ALL permissions + bypass via Gate::before
 *    (configured in AuthServiceProvider).
 *  - Existing backend_user records are mapped to roles based on their linked
 *    employee.sma_user matching the legacy config arrays.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** Mirrors `config/permissions.php` from the legacy project. */
    private array $legacyConfig = [
        'super_admins' => [16, 18, 19, 23, 36, 54, 77, 95, 108, 121, 146, 151, 1, 15, 24, 46, 51, 64],

        'groups' => [
            'production-operator' => [
                'sma_users'  => [160],
                'default_route' => 'packing',
                'permissions' => [
                    'packing', 'saledata_packing', 'deliveries', 'saledata_deliveries',
                    'manufacturing.calendar.*', 'production.recipes.*', 'production.tester.*',
                    'product.updateQuantity', 'trello.tea.*', 'trello.perfume.*', 'trello.brand.*',
                    'productlist', 'productlisttester', 'productnew', 'savenew_product',
                    'productadjust', 'transfer', 'productcheckstock', 'quantity_comparison',
                    'productadjust_new', 'productadjust_save', 'adjust_delete', 'adjust_detail',
                    'transfer_new', 'transfer_addtotemp', 'transfer_save', 'transfer_removetemp',
                    'transfer_delete', 'transfer_detail', 'transfer_checkqty', 'export_transfer',
                ],
            ],

            'production-maintainer' => [
                'sma_users'  => [166],
                'default_route' => 'manufacturing.calendar.end.process.index',
                'permissions' => [
                    'manufacturing.calendar.end.process.index',
                    'trello.tea.*', 'trello.perfume.*', 'trello.brand.*',
                    'trello.brand.items', 'trello.category.*', 'trello.item.*', 'trello.update.order',
                    'productlist', 'productlisttester', 'productnew', 'savenew_product',
                    'productadjust', 'transfer', 'productcheckstock', 'quantity_comparison',
                    'productadjust_new', 'productadjust_save', 'adjust_delete', 'adjust_detail',
                    'transfer_new', 'transfer_addtotemp', 'transfer_save', 'transfer_removetemp',
                    'transfer_delete', 'transfer_detail', 'transfer_checkqty', 'export_transfer',
                ],
            ],

            'accountant' => [
                'sma_users'  => [99, 146, 151],
                'default_route' => 'dashboard',
                'permissions' => ['*'], // full access, but uses specialized sidebar (per legacy comment)
            ],
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ---- 1. Create permissions (deduped) ----
        $allPermissions = collect($this->legacyConfig['groups'])
            ->pluck('permissions')
            ->flatten()
            ->unique()
            ->reject(fn ($p) => $p === '*')
            ->values();

        foreach ($allPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->command->info("✓ Created {$allPermissions->count()} permissions.");

        // ---- 2. Create roles ----
        $superAdmin           = Role::firstOrCreate(['name' => 'super-admin',           'guard_name' => 'web']);
        $productionOperator   = Role::firstOrCreate(['name' => 'production-operator',   'guard_name' => 'web']);
        $productionMaintainer = Role::firstOrCreate(['name' => 'production-maintainer', 'guard_name' => 'web']);
        $accountant           = Role::firstOrCreate(['name' => 'accountant',            'guard_name' => 'web']);

        // ---- 3. Attach permissions to roles ----
        $superAdmin->syncPermissions(Permission::all());                   // everything
        $accountant->syncPermissions(Permission::all());                   // legacy comment: "full access for now"

        $productionOperator->syncPermissions(
            Permission::whereIn('name', $this->legacyConfig['groups']['production-operator']['permissions'])->get()
        );
        $productionMaintainer->syncPermissions(
            Permission::whereIn('name', $this->legacyConfig['groups']['production-maintainer']['permissions'])->get()
        );

        $this->command->info('✓ Created 4 roles and attached permissions.');

        // ---- 4. Assign roles to existing users (skip silently if hr_employee not present) ----
        if (! DB::getSchemaBuilder()->hasTable('hr_employee') || ! DB::getSchemaBuilder()->hasTable('backend_user')) {
            $this->command->warn('⚠ Legacy tables `hr_employee` / `backend_user` not found — skipping user→role mapping.');
            $this->command->warn('  Run this seeder AGAIN once you have pointed `.env` at the legacy MySQL DB.');
            return;
        }

        $assigned = 0;
        $assigned += $this->assignBySmaIds($this->legacyConfig['super_admins'],                       $superAdmin->name);
        $assigned += $this->assignBySmaIds($this->legacyConfig['groups']['production-operator']['sma_users'],   $productionOperator->name);
        $assigned += $this->assignBySmaIds($this->legacyConfig['groups']['production-maintainer']['sma_users'], $productionMaintainer->name);
        $assigned += $this->assignBySmaIds($this->legacyConfig['groups']['accountant']['sma_users'],            $accountant->name);

        $this->command->info("✓ Assigned roles to {$assigned} users.");
    }

    private function assignBySmaIds(array $smaIds, string $roleName): int
    {
        $users = User::query()
            ->whereHas('employeeRecord', fn ($q) => $q->whereIn('sma_user', $smaIds))
            ->get();

        foreach ($users as $u) {
            $u->assignRole($roleName);
        }

        return $users->count();
    }
}
