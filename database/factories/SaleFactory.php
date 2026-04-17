<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 5000);
        $tax = round($subtotal * 0.10, 2);
        $discount = 0;
        $total = $subtotal + $tax - $discount;

        return [
            'reference' => 'SO-'.now()->format('Y').'-'.fake()->unique()->numerify('#####'),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'status' => SaleStatus::Paid,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'paid_at' => now(),
            'source' => 'web',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => SaleStatus::Pending, 'paid_at' => null]);
    }
}
