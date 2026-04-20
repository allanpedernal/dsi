<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;

/**
 * Read/write operations for Spatie permissions, with role usage counts.
 */
class PermissionService
{
    /**
     * Paginate permissions with role usage counts and optional name search.
     *
     * @param  array{search?: ?string, per_page?: ?int}  $filters
     * @return LengthAwarePaginator<int, Permission>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 25)));

        return Permission::query()
            ->withCount('roles')
            ->when($filters['search'] ?? null, fn ($q, $t) => $q->where('name', 'like', "%{$t}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new permission, defaulting the guard to `web`.
     *
     * @param  array{name: string, guard_name?: ?string}  $data
     */
    public function create(array $data): Permission
    {
        return Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);
    }

    /**
     * Update an existing permission's name and/or guard.
     *
     * @param  array{name: string, guard_name?: ?string}  $data
     */
    public function update(Permission $permission, array $data): Permission
    {
        $permission->update([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? $permission->guard_name,
        ]);

        return $permission->refresh();
    }

    /** Remove a permission; cascades any role/user assignments by Spatie. */
    public function delete(Permission $permission): void
    {
        $permission->delete();
    }
}
