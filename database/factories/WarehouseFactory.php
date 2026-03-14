<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->word()).' Warehouse',
            'description' => $this->faker->paragraph(),
            'manager_id' => User::factory(),
        ];
    }
}
