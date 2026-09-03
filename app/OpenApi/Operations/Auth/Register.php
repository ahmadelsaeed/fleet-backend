<?php

namespace App\OpenApi\Operations\Auth;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\AuthTokenData;
use App\OpenApi\Schemas\RegisterRequest;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Register extends OA\Post
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/register',
            operationId: 'authRegister',
            summary: 'Register user',
            tags: ['Auth'],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: RegisterRequest::class),
            ),
            responses: [
                new SuccessResponse(
                    description: 'User registered successfully',
                    messageExample: 'Login Successfully',
                    data: AuthTokenData::class,
                ),
                new ErrorResponse(
                    description: 'Validation error',
                    messageExample: 'Invalid data',
                    response: 422,
                ),
            ],
        );
    }
}
