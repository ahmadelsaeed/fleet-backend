<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '+201234567890'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
    ],
    type: 'object'
)]
final class RegisterRequest {}
