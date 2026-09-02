<?php

namespace Database\Factories;

use App\Models\Bus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusFactory extends Factory
{
    protected $model = Bus::class;

    public function definition(): array
    {
        return [
            'plate_number' => fake()->unique()->bothify('????-####'),
            'seats_count' => 12,
        ];
    }
}
