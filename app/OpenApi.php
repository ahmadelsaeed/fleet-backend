<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Company - Service',
    description: 'Service documentation',
    contact: new OA\Contact(email: 'ahmad.elsaeed.ali@gmail.com'),
    license: new OA\License(
        name: 'Apache 2.0',
        url: 'https://www.apache.org/licenses/LICENSE-2.0.html'
    )
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Demo API Server'
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Authentication'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    description: 'Laravel Sanctum bearer token. Format: Bearer {token}',
    bearerFormat: 'token',
    scheme: 'bearer'
)]
final class OpenApi {}
