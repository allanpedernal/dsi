<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function list(): Collection
    {
        return Role::with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    public function permissions(): Collection
    {
        return Permission::orderBy('name')->get(['id', 'name']);
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    public function syncPermissions(Role $role, array $permissionNames): Role
    {
        $role->syncPermissions($permissionNames);

        return $role->load('permissions:id,name');
    }

    public function create(string $name): Role
    {
        return Role::create(['name' => $name, 'guard_name' => 'web'])
            ->load('permissions:id,name');
    }

    public function rename(Role $role, string $name): Role
    {
        $role->update(['name' => $name]);

        return $role->load('permissions:id,name');
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
