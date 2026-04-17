<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $cost = fake()->randomFloat(2, 5, 500);

        return [
            'sku' => 'SKU-'.strtoupper(fake()->unique()->bothify('???-####')),
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'category_id' => Category::factory(),
            'description' => fake()->paragraph(),
            'price' => round($cost * fake()->randomFloat(2, 1.2, 2.5), 2),
            'cost' => $cost,
            'stock' => fake()->numberBetween(0, 200),
            'reorder_level' => 10,
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['stock' => fake()->numberBetween(0, 5)]);
    }
}
