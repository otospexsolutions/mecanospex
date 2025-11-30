<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions per module
        $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles();
    }

    /**
     * Create all permissions organized by module.
     */
    private function createPermissions(): void
    {
        $permissions = [
            // Partner/Customer Management
            'partners.view',
            'partners.create',
            'partners.update',
            'partners.delete',

            // Product/Catalog Management
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.import',

            // Vehicle Management
            'vehicles.view',
            'vehicles.create',
            'vehicles.update',
            'vehicles.delete',

            // Sales Documents (Quotes, Orders, Invoices)
            'quotes.view',
            'quotes.create',
            'quotes.update',
            'quotes.delete',
            'quotes.convert',

            'orders.view',
            'orders.create',
            'orders.update',
            'orders.delete',
            'orders.confirm',

            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.post',
            'invoices.cancel',
            'invoices.print',

            'credit-notes.view',
            'credit-notes.create',
            'credit-notes.post',

            // Inventory Management
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer',
            'inventory.receive',

            'deliveries.view',
            'deliveries.create',
            'deliveries.confirm',

            // Treasury/Payments
            'payments.view',
            'payments.create',
            'payments.allocate',
            'payments.void',

            'instruments.view',
            'instruments.create',
            'instruments.transfer',
            'instruments.clear',

            'repositories.view',
            'repositories.manage',

            // Accounting
            'journal.view',
            'journal.create',
            'journal.post',

            'accounts.view',
            'accounts.manage',

            'reports.financial',
            'reports.operational',

            // Workshop
            'work-orders.view',
            'work-orders.create',
            'work-orders.update',
            'work-orders.complete',

            // User & Tenant Management
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign-roles',

            'roles.view',
            'roles.manage',

            // System
            'settings.view',
            'settings.update',
            'audit.view',
            'imports.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        $this->command->info('Created '.count($permissions).' permissions');
    }

    /**
     * Create roles and assign permissions.
     */
    private function createRoles(): void
    {
        // Admin - Full access
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(Permission::all());
        $this->command->info('Created role: admin (all permissions)');

        // Manager - Operations management
        $manager = Role::create(['name' => 'manager', 'guard_name' => 'sanctum']);
        $manager->givePermissionTo([
            'partners.view', 'partners.create', 'partners.update',
            'products.view', 'products.create', 'products.update', 'products.import',
            'vehicles.view', 'vehicles.create', 'vehicles.update',
            'quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert',
            'orders.view', 'orders.create', 'orders.update', 'orders.confirm',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.post', 'invoices.print',
            'credit-notes.view', 'credit-notes.create', 'credit-notes.post',
            'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.receive',
            'deliveries.view', 'deliveries.create', 'deliveries.confirm',
            'payments.view', 'payments.create', 'payments.allocate',
            'instruments.view', 'instruments.create', 'instruments.transfer',
            'repositories.view',
            'journal.view',
            'accounts.view',
            'reports.financial', 'reports.operational',
            'work-orders.view', 'work-orders.create', 'work-orders.update', 'work-orders.complete',
            'users.view',
            'settings.view',
        ]);
        $this->command->info('Created role: manager');

        // Cashier - Point of sale operations
        $cashier = Role::create(['name' => 'cashier', 'guard_name' => 'sanctum']);
        $cashier->givePermissionTo([
            'partners.view', 'partners.create',
            'products.view',
            'vehicles.view',
            'quotes.view', 'quotes.create',
            'orders.view',
            'invoices.view', 'invoices.create', 'invoices.print',
            'inventory.view',
            'deliveries.view',
            'payments.view', 'payments.create',
            'instruments.view', 'instruments.create',
            'work-orders.view',
        ]);
        $this->command->info('Created role: cashier');

        // Viewer - Read-only access
        $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'sanctum']);
        $viewer->givePermissionTo([
            'partners.view',
            'products.view',
            'vehicles.view',
            'quotes.view',
            'orders.view',
            'invoices.view',
            'credit-notes.view',
            'inventory.view',
            'deliveries.view',
            'payments.view',
            'instruments.view',
            'repositories.view',
            'journal.view',
            'accounts.view',
            'reports.operational',
            'work-orders.view',
        ]);
        $this->command->info('Created role: viewer');

        // Technician - Workshop operations
        $technician = Role::create(['name' => 'technician', 'guard_name' => 'sanctum']);
        $technician->givePermissionTo([
            'partners.view',
            'products.view',
            'vehicles.view',
            'inventory.view',
            'work-orders.view', 'work-orders.update', 'work-orders.complete',
        ]);
        $this->command->info('Created role: technician');

        // Accountant - Financial operations
        $accountant = Role::create(['name' => 'accountant', 'guard_name' => 'sanctum']);
        $accountant->givePermissionTo([
            'partners.view',
            'invoices.view', 'invoices.post',
            'credit-notes.view', 'credit-notes.post',
            'payments.view', 'payments.create', 'payments.allocate',
            'instruments.view', 'instruments.transfer', 'instruments.clear',
            'repositories.view', 'repositories.manage',
            'journal.view', 'journal.create', 'journal.post',
            'accounts.view', 'accounts.manage',
            'reports.financial',
            'audit.view',
        ]);
        $this->command->info('Created role: accountant');
    }
}
