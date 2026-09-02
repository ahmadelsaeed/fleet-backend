<?php

namespace Database\Factories;

use App\Models\Bus;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('T###'),
            'bus_id' => Bus::factory(),
            'date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }
}
