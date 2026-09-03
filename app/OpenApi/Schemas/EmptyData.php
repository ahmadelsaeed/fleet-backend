<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EmptyData',
    type: 'object',
    properties: []
)]
final class EmptyData {}
