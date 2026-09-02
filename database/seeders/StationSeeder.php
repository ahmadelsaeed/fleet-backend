<?php

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Seeder;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        $stations = ['Cairo', 'Giza', 'Al Fayyum', 'Al Minya', 'Asyut'];

        foreach ($stations as $name) {
            Station::firstOrCreate(['name' => $name]);
        }
    }
}
