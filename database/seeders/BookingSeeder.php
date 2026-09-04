<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Seat;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $seedBookings = [
            [
                'trip_code' => '001',
                'user_email' => 'ahmed@example.com',
                'seat_number' => 1,
            ],
            [
                'trip_code' => '003',
                'user_email' => 'mohamed@example.com',
                'seat_number' => 2,
            ],
        ];

        foreach ($seedBookings as $bookingData) {
            $trip = Trip::with('tripStops')->where('code', $bookingData['trip_code'])->firstOrFail();
            $user = User::where('email', $bookingData['user_email'])->firstOrFail();
            $seat = Seat::query()
                ->where('bus_id', $trip->bus_id)
                ->where('seat_number', $bookingData['seat_number'])
                ->firstOrFail();

            $startStop = $trip->tripStops->first();
            $endStop = $trip->tripStops->last();

            Booking::firstOrCreate(
                [
                    'trip_id' => $trip->id,
                    'seat_id' => $seat->id,
                    'user_id' => $user->id,
                ],
                [
                    'start_trip_stop_id' => $startStop?->id,
                    'end_trip_stop_id' => $endStop?->id,
                ]
            );
        }
    }
}
