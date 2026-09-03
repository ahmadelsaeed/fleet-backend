<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthTokenData',
    required: ['user', 'token'],
    properties: [
        new OA\Property(property: 'user', ref: User::class),
        new OA\Property(property: 'token', type: 'string', example: '1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
    ],
    type: 'object'
)]
final class AuthTokenData {}
