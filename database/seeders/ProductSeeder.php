<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $categories = Category::all();

        if ($categories->isEmpty()) {
            return;
        }

        Product::factory(30)
            ->state(fn () => ['category_id' => $categories->random()->id, 'created_by' => $admin?->id])
            ->create();

        Product::factory(5)
            ->lowStock()
            ->state(fn () => ['category_id' => $categories->random()->id, 'created_by' => $admin?->id])
            ->create();
    }
}
