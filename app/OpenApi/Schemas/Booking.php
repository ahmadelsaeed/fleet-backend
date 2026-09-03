<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Booking',
    required: ['id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'trip', ref: Trip::class),
        new OA\Property(property: 'seat_id', type: 'integer', example: 5),
        new OA\Property(property: 'start_trip_stop_id', type: 'integer', example: 2),
        new OA\Property(property: 'end_trip_stop_id', type: 'integer', example: 4),
    ],
    type: 'object'
)]
final class Booking {}
