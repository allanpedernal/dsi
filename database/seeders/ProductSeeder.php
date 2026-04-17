<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $categories = Category::all();
        $customers = Customer::pluck('id');

        if ($categories->isEmpty() || $customers->isEmpty()) {
            return;
        }

        // Create products distributed across customers.
        foreach ($customers as $customerId) {
            Product::factory(6)
                ->state(fn () => [
                    'category_id' => $categories->random()->id,
                    'customer_id' => $customerId,
                    'created_by' => $admin?->id,
                ])
                ->create();

            // 1 low-stock product per customer.
            Product::factory()
                ->lowStock()
                ->state(fn () => [
                    'category_id' => $categories->random()->id,
                    'customer_id' => $customerId,
                    'created_by' => $admin?->id,
                ])
                ->create();
        }
    }
}
