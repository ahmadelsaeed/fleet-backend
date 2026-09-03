<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TripsList',
    required: ['trips'],
    properties: [
        new OA\Property(property: 'trips', type: 'array', items: new OA\Items(ref: Trip::class)),
    ],
    type: 'object'
)]
final class TripsList {}
