<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ErrorResponse extends OA\Response
{
    public function __construct(
        string $description = 'Request failed',
        string $messageExample = 'Invalid credentials',
        int|string $response = 400,
    ) {
        parent::__construct(
            response: $response,
            description: $description,
            content: new OA\JsonContent(
                required: ['success', 'message', 'data'],
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: false),
                    new OA\Property(property: 'message', type: 'string', example: $messageExample),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string')),
                ],
                type: 'object',
            ),
        );
    }
}
