<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $seed = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => UserRole::Admin],
            ['name' => 'Manager User', 'email' => 'manager@example.com', 'role' => UserRole::Manager],
            ['name' => 'Cashier Jane', 'email' => 'cashier@example.com', 'role' => UserRole::Cashier],
        ];

        foreach ($seed as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                ['name' => $row['name'], 'password' => Hash::make('password')]
            );
            $user->syncRoles([$row['role']->value]);
        }
    }
}
