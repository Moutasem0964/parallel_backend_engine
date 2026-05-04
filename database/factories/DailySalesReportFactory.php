<?php

namespace Database\Factories;

use App\Models\DailySalesReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailySalesReportFactory extends Factory
{
    protected $model = DailySalesReport::class;

    public function definition(): array
    {
        return [
            'report_date' => fake()->unique()->date(),
            'status' => DailySalesReport::STATUS_PENDING,
            'total_chunks' => 0,
            'processed_chunks' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
