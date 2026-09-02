<?php

namespace Database\Seeders;

use App\Models\Bus;
use Illuminate\Database\Seeder;

class BusSeeder extends Seeder
{
    public function run(): void
    {
        $buses = [
            ['plate_number' => '1234', 'seats_count' => 12],
            ['plate_number' => '5678', 'seats_count' => 12],
        ];

        foreach ($buses as $bus) {
            Bus::firstOrCreate(
                ['plate_number' => $bus['plate_number']],
                ['seats_count' => $bus['seats_count']]
            );
        }
    }
}
