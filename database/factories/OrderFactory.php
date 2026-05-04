<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_ref' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'status' => Order::STATUS_PENDING,
            'total_price' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn() => ['status' => Order::STATUS_COMPLETED]);
    }

    public function failed(): static
    {
        return $this->state(fn() => ['status' => Order::STATUS_FAILED]);
    }

    public function forDate(string $date): static
    {
        return $this->state(fn() => [
            'created_at' => $date . ' ' . fake()->time(),
            'updated_at' => $date . ' ' . fake()->time(),
        ]);
    }

    public function withoutUser(): static
    {
        return $this->state(fn() => ['user_id' => null]);
    }
}
