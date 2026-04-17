<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /**
     * @param  array{search?: ?string, per_page?: ?int}  $filters
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

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }
}
