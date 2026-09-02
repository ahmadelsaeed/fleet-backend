<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Seat;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'seat_id' => Seat::factory(),
            'user_id' => User::factory(),
            'start_trip_stop_id' => TripStop::factory(),
            'end_trip_stop_id' => TripStop::factory(),
        ];
    }
}
