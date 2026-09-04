<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StationSeeder::class,
            BusSeeder::class,
            SeatSeeder::class,
            TripSeeder::class,
            TripStopSeeder::class,
            UserSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
