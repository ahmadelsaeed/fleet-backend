<?php

namespace App\OpenApi\Operations\Auth;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\EmptyData;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Logout extends OA\Post
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/logout',
            operationId: 'authLogout',
            summary: 'Logout user',
            security: [
                ['sanctum' => []],
            ],
            tags: ['Auth'],
            responses: [
                new SuccessResponse(
                    description: 'Logged out successfully',
                    messageExample: 'Logged out successfully',
                    data: EmptyData::class,
                ),
                new ErrorResponse(
                    description: 'Unauthorized',
                    messageExample: 'Unauthenticated',
                    response: 401,
                ),
            ],
        );
    }
}
