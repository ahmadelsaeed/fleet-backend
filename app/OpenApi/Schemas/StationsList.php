<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StationsList',
    required: ['stations'],
    properties: [
        new OA\Property(property: 'stations', type: 'array', items: new OA\Items(ref: Station::class)),
    ],
    type: 'object'
)]
final class StationsList {}
