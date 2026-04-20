<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

/**
 * Read/write operations for users, encapsulating role assignment and password hashing.
 */
class UserService
{
    /**
     * Paginate users with their roles, filtered by search term and/or single role.
     *
     * @param  array{search?: ?string, role?: ?string, per_page?: ?int}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));

        return User::query()
            ->with('roles:id,name')
            ->when($filters['search'] ?? null, function ($q, $t) {
                $q->where(fn ($q) => $q->where('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%"));
            })
            ->when($filters['role'] ?? null, fn ($q, $r) => $q->whereHas('roles', fn ($q) => $q->where('name', $r)))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Create a new user with a hashed password and optional single-role assignment.
     *
     * @param  array{name:string, email:string, password:string, role?:?string}  $data
     */
    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (! empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user;
    }

    /**
     * Update an existing user; re-hashes the password only when one is provided.
     *
     * @param  array{name?:string, email?:string, password?:?string, role?:?string}  $data
     */
    public function update(User $user, array $data): User
    {
        $update = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }
        $user->update($update);

        if (array_key_exists('role', $data) && $data['role']) {
            $user->syncRoles([$data['role']]);
        }

        return $user->refresh();
    }

    /** Delete a user; callers are responsible for preventing self-deletion. */
    public function delete(User $user): void
    {
        $user->delete();
    }
}
