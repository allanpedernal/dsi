<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(SaleService $sales, int $count = 25): void
    {
        $cashier = User::where('email', 'cashier@example.com')->first() ?? User::first();
        $customers = Customer::pluck('id');

        if ($customers->isEmpty() || ! $cashier) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $available = Product::where('stock', '>', 0)->get();
            if ($available->isEmpty()) {
                break;
            }

            $picks = $available->random(min($available->count(), rand(1, 4)));

            $items = $picks->map(fn ($p) => [
                'product_id' => $p->id,
                'quantity' => min($p->stock, rand(1, 3)),
            ])->filter(fn ($item) => $item['quantity'] > 0)->values()->all();

            if (empty($items)) {
                continue;
            }

            $sales->create([
                'customer_id' => $customers->random(),
                'user_id' => $cashier->id,
                'items' => $items,
                'tax_rate' => 0.10,
                'discount' => 0,
                'source' => 'web',
            ], actingUser: $cashier);
        }
    }
}
