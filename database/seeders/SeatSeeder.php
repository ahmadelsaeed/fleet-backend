<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        Bus::all()->each(function (Bus $bus) {
            for ($seatNumber = 1; $seatNumber <= $bus->seats_count; $seatNumber++) {
                Seat::firstOrCreate([
                    'bus_id' => $bus->id,
                    'seat_number' => $seatNumber,
                ]);
            }
        });
    }
}
