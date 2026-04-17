<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);

        $admin = User::where('email', 'admin@example.com')->first();
        if ($admin) {
            Auth::setUser($admin);
        }

        $this->call([
            CategorySeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
        ]);

        $cashier = User::where('email', 'cashier@example.com')->first();
        if ($cashier) {
            Auth::setUser($cashier);
        }

        $this->call([
            SaleSeeder::class,
        ]);
    }
}
