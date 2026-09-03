<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['login', 'password'],
    properties: [
        new OA\Property(property: 'login', type: 'string', example: 'user@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
    ],
    type: 'object'
)]
final class LoginRequest {}
