<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(SaleService $sales): void
    {
        $manager = User::where('email', 'manager@example.com')->first() ?? User::first();
        $customers = Customer::all();

        if ($customers->isEmpty() || ! $manager) {
            return;
        }

        // Create 5 sales per customer using that customer's own products.
        foreach ($customers as $customer) {
            $available = Product::where('customer_id', $customer->id)
                ->where('stock', '>', 0)
                ->get();

            if ($available->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < 5; $i++) {
                $current = $available->filter(fn ($p) => $p->stock > 0);
                if ($current->isEmpty()) {
                    break;
                }

                $picks = $current->random(min($current->count(), rand(1, 3)));
                $items = $picks->map(fn ($p) => [
                    'product_id' => $p->id,
                    'quantity' => min($p->stock, rand(1, 2)),
                ])->filter(fn ($item) => $item['quantity'] > 0)->values()->all();

                if (empty($items)) {
                    continue;
                }

                $sales->create([
                    'customer_id' => $customer->id,
                    'user_id' => $manager->id,
                    'items' => $items,
                    'tax_rate' => 0.10,
                    'discount' => 0,
                    'source' => 'web',
                ], actingUser: $manager);

                // Refresh stock after sale.
                foreach ($items as $item) {
                    $product = $available->firstWhere('id', $item['product_id']);
                    if ($product) {
                        $product->stock -= $item['quantity'];
                    }
                }
            }
        }
    }
}
