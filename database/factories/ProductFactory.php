<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $adjectives = [
            'Premium', 'Classic', 'Ultra', 'Smart', 'Pro', 'Eco', 'Compact',
        ];

        $products = [
            'Backpack', 'Headphones', 'Office Chair', 'Mechanical Keyboard',
            'Wireless Mouse', 'Desk Lamp', 'Smart Watch', 'Power Bank',
        ];

        $name = fake()->randomElement($adjectives).' '.fake()->randomElement($products);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 500),
            'sku' => fake()->unique()->bothify('SKU-########'),
            'code' => fake()->unique()->bothify('CODE-####'),
            'quantity' => fake()->numberBetween(1, 100),
            'warehouse_id' => Warehouse::factory(),
            'created_at' => fake()->dateTimeBetween('-19 years', 'now'),
            'updated_at' => now(),
        ];
    }
}
