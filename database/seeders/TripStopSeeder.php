<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\Trip;
use App\Models\TripStop;
use Illuminate\Database\Seeder;

class TripStopSeeder extends Seeder
{
    public function run(): void
    {
        $this->createRoute('001', [
            'Cairo', 'Al Fayyum', 'Al Minya', 'Asyut',
        ]);

        $this->createRoute('002', [
            'Giza', 'Al Fayyum', 'Asyut',
        ]);

        $this->createRoute('003', [
            'Cairo', 'Al Fayyum', 'Asyut',
        ]);

        $this->createRoute('004', [
            'Giza', 'Al Fayyum', 'Al Minya', 'Asyut',
        ]);
    }

    private function createRoute(string $tripCode, array $stationNamesInOrder): void
    {
        $trip = Trip::where('code', $tripCode)->firstOrFail();

        foreach ($stationNamesInOrder as $index => $stationName) {
            $station = Station::where('name', $stationName)->firstOrFail();

            TripStop::firstOrCreate([
                'trip_id' => $trip->id,
                'station_id' => $station->id,
            ], [
                'sequence_order' => $index + 1,
            ]);
        }
    }
}
