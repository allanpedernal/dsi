<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Read/write operations for Spatie roles and their permission assignments.
 */
class RoleService
{
    /**
     * List every role eager-loading permissions and user counts for the admin UI.
     *
     * @return Collection<int, Role>
     */
    public function list(): Collection
    {
        return Role::with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    /**
     * List every permission (id + name) alphabetically for assignment pickers.
     *
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        return Permission::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Replace the given role's permission set with the supplied names.
     *
     * @param  array<int, string>  $permissionNames
     */
    public function syncPermissions(Role $role, array $permissionNames): Role
    {
        $role->syncPermissions($permissionNames);

        return $role->load('permissions:id,name');
    }

    /** Create a new role on the `web` guard with no permissions. */
    public function create(string $name): Role
    {
        return Role::create(['name' => $name, 'guard_name' => 'web'])
            ->load('permissions:id,name');
    }

    /** Rename an existing role, preserving its permission assignments. */
    public function rename(Role $role, string $name): Role
    {
        $role->update(['name' => $name]);

        return $role->load('permissions:id,name');
    }

    /** Remove a role; its assignments are cascaded by Spatie. */
    public function delete(Role $role): void
    {
        $role->delete();
    }
}
