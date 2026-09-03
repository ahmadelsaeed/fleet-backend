<?php

namespace App\OpenApi\Operations\Trips;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\Trip;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class TripShow extends OA\Get
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/trips/{trip}',
            operationId: 'tripsShow',
            summary: 'Get trip details',
            tags: ['Trips'],
            parameters: [
                new OA\PathParameter(name: 'trip', description: 'Trip id', required: true, schema: new OA\Schema(type: 'integer')),
            ],
            responses: [
                new SuccessResponse(
                    description: 'Trip retrieved successfully',
                    messageExample: 'Trip retrieved successfully',
                    data: Trip::class,
                ),
                new ErrorResponse(
                    description: 'Not found',
                    messageExample: 'Trip not found',
                    response: 404,
                ),
            ],
        );
    }
}
