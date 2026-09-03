<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Trip',
    required: ['id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'departure_at', type: 'string', example: '2026-09-03T10:00:00Z'),
        new OA\Property(property: 'bus', type: 'object'),
        new OA\Property(property: 'tripStops', type: 'array', items: new OA\Items(type: 'object')),
    ],
    type: 'object'
)]
final class Trip {}
