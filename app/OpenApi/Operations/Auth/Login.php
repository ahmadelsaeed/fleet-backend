<?php

namespace App\OpenApi\Operations\Auth;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\AuthTokenData;
use App\OpenApi\Schemas\LoginRequest;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Login extends OA\Post
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/login',
            operationId: 'authLogin',
            summary: 'Login user',
            tags: ['Auth'],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: LoginRequest::class),
            ),
            responses: [
                new SuccessResponse(
                    description: 'User logged in successfully',
                    messageExample: 'Login Successfully',
                    data: AuthTokenData::class,
                ),
                new ErrorResponse(
                    description: 'Invalid credentials',
                    messageExample: 'Invalid credentials',
                ),
            ],
        );
    }
}
