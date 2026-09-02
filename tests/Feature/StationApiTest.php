<?php

use App\Models\Station;

it('returns all stations', function () {
    Station::factory()->count(3)->create();

    $response = $this->getJson('/api/stations');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'stations' => [
                    '*' => ['id', 'name'],
                ],
            ],
        ]);

    expect($response->json('data.stations'))->toHaveCount(3);
});

it('returns empty list when no stations exist', function () {
    $response = $this->getJson('/api/stations');

    $response->assertOk();
    expect($response->json('data.stations'))->toBeEmpty();
});