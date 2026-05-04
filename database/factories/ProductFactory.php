<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(50, 500),
            'version' => 0,
        ];
    }

    public function lastItem(): static
    {
        return $this->state(fn() => [
            'name' => 'Last Item — ' . fake()->word(),
            'stock' => 1,
        ]);
    }

    public function lowStock(int $stock = 5): static
    {
        return $this->state(fn() => ['stock' => $stock]);
    }

    public function bulk(): static
    {
        return $this->state(fn() => ['stock' => 1000]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn() => ['stock' => 0]);
    }
}
