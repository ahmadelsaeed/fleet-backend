<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SeatsAvailabilityResponse',
    required: ['trip_id', 'start_station_id', 'end_station_id', 'seats'],
    properties: [
        new OA\Property(property: 'trip_id', type: 'integer', example: 1),
        new OA\Property(property: 'start_station_id', type: 'integer', example: 1),
        new OA\Property(property: 'end_station_id', type: 'integer', example: 2),
        new OA\Property(property: 'seats', type: 'array', items: new OA\Items(ref: SeatAvailability::class)),
    ],
    type: 'object'
)]
final class SeatsAvailabilityResponse {}
