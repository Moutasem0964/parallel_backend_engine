<?php

namespace Database\Factories;

use App\Models\JobMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobMetricFactory extends Factory
{
    protected $model = JobMetric::class;

    public function definition(): array
    {
        return [
            'job_class' => 'App\\Jobs\\ProcessOrder',
            'duration_ms' => fake()->randomFloat(2, 2, 30),
            'succeeded' => true,
            'attempt' => 1,
            'created_at' => now(),
        ];
    }
}
