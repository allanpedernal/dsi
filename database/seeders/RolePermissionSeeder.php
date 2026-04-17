<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /** @var array<int, string> */
    private array $permissions = [
        'dashboard.view',
        'customers.view', 'customers.create', 'customers.update', 'customers.delete',
        'products.view', 'products.create', 'products.update', 'products.delete',
        'sales.view', 'sales.create', 'sales.update', 'sales.delete',
        'users.view', 'users.create', 'users.update', 'users.delete',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'permissions.view', 'permissions.create', 'permissions.update', 'permissions.delete',
        'reports.view',
        'audit.view',
    ];

    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => UserRole::Manager->value, 'guard_name' => 'web']);
        $manager->syncPermissions([
            'dashboard.view',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'sales.view', 'sales.create', 'sales.update', 'sales.delete',
            'reports.view',
            'audit.view',
        ]);

        $customer = Role::firstOrCreate(['name' => UserRole::Customer->value, 'guard_name' => 'web']);
        $customer->syncPermissions([
            'dashboard.view',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'sales.view', 'sales.create', 'sales.update', 'sales.delete',
            'reports.view',
        ]);
    }
}
