<?php

namespace Database\Factories;

use App\Models\BatchFailedRecord;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFailedRecordFactory extends Factory
{
    protected $model = BatchFailedRecord::class;

    public function definition(): array
    {
        return [
            'report_date' => fake()->date(),
            'order_id' => Order::factory(),
            'chunk_number' => fake()->numberBetween(1, 10),
            'error_message' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
