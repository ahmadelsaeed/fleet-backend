<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BookingStoreRequest',
    required: ['trip_id', 'seat_id', 'start_station_id', 'end_station_id'],
    properties: [
        new OA\Property(property: 'trip_id', type: 'integer', example: 1),
        new OA\Property(property: 'seat_id', type: 'integer', example: 5),
        new OA\Property(property: 'start_station_id', type: 'integer', example: 2),
        new OA\Property(property: 'end_station_id', type: 'integer', example: 4),
    ],
    type: 'object'
)]
final class BookingStoreRequest {}
