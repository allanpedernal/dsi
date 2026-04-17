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

        $manager = User::where('email', 'manager@example.com')->first();
        if ($manager) {
            Auth::setUser($manager);
        }

        $this->call([
            SaleSeeder::class,
        ]);
    }
}
