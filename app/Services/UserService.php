<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * @param  array{search?: ?string, role?: ?string, per_page?: ?int}  $filters
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

    public function delete(User $user): void
    {
        $user->delete();
    }
}
