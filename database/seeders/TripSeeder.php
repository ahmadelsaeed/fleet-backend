<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\Trip;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $busOne = Bus::where('plate_number', '1234')->firstOrFail();
        $busTwo = Bus::where('plate_number', '5678')->firstOrFail();

        Trip::firstOrCreate(
            ['code' => '001'],
            ['bus_id' => $busOne->id, 'date' => now()->addDay()->toDateString()]
        );
        Trip::firstOrCreate(
            ['code' => '003'],
            ['bus_id' => $busTwo->id, 'date' => now()->addDay()->toDateString()]
        );

        Trip::firstOrCreate(
            ['code' => '002'],
            ['bus_id' => $busTwo->id, 'date' => now()->addDays(2)->toDateString()]
        );
        Trip::firstOrCreate(
            ['code' => '004'],
            ['bus_id' => $busOne->id, 'date' => now()->addDays(2)->toDateString()]
        );
    }
}
