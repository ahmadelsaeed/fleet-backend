<?php

namespace App\OpenApi\Operations\Profile;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\User;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Me extends OA\Get
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/me',
            operationId: 'profileMe',
            summary: 'Get authenticated user profile',
            tags: ['Profile'],
            responses: [
                new SuccessResponse(
                    description: 'User profile retrieved successfully',
                    messageExample: 'User profile retrieved successfully',
                    data: User::class,
                ),
                new ErrorResponse(
                    description: 'Unauthenticated',
                    messageExample: 'Unauthenticated',
                    response: 401,
                ),
            ],
        );
    }
}
