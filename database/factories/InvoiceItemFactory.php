<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition()
    {
        return [
            'description' => $this->faker->words(3, true),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'line_total' => function (array $attributes) {
                return $attributes['quantity'] * $attributes['unit_price'];
            },
        ];
    }
}
