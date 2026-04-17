<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CustomerSeeder extends Seeder
{
    public function run(int $count = 5): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        Role::findOrCreate(UserRole::Customer->value, 'web');

        for ($i = 0; $i < $count; $i++) {
            $customer = Customer::factory()->create([
                'created_by' => $admin?->id,
            ]);

            // Create a login account for each customer and link via pivot.
            $user = User::create([
                'name' => $customer->full_name,
                'email' => $customer->email,
                'password' => Hash::make('password'),
            ]);
            $user->assignRole(UserRole::Customer->value);
            $user->customers()->attach($customer->id);
        }
    }
}
