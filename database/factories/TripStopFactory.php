<?php

namespace Database\Factories;

use App\Models\Station;
use App\Models\Trip;
use App\Models\TripStop;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripStopFactory extends Factory
{
    protected $model = TripStop::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'station_id' => Station::factory(),
            'sequence_order' => fake()->unique()->numberBetween(1, 10),
        ];
    }
}
