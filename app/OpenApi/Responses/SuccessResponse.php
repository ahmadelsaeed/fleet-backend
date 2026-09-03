<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SuccessResponse extends OA\Response
{
    public function __construct(
        string $description,
        string $messageExample,
        string|object $data,
        int|string $response = 200,
    ) {
        parent::__construct(
            response: $response,
            description: $description,
            content: new OA\JsonContent(
                required: ['success', 'message', 'data'],
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: $messageExample),
                    new OA\Property(property: 'data', ref: $data),
                ],
                type: 'object',
            ),
        );
    }
}
