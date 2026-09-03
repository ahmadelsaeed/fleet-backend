<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SeatAvailability',
    required: ['seat_id', 'available'],
    properties: [
        new OA\Property(property: 'seat_id', type: 'integer', example: 1),
        new OA\Property(property: 'available', type: 'boolean', example: true),
    ],
    type: 'object'
)]
final class SeatAvailability {}
