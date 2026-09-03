<?php

namespace App\OpenApi\Operations\Trips;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\TripsList;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class TripsIndex extends OA\Get
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/trips',
            operationId: 'tripsIndex',
            summary: 'List trips',
            tags: ['Trips'],
            responses: [
                new SuccessResponse(
                    description: 'Trips retrieved successfully',
                    messageExample: 'Trips retrieved successfully',
                    data: TripsList::class,
                ),
                new ErrorResponse(
                    description: 'Request failed',
                    messageExample: 'Request failed',
                ),
            ],
        );
    }
}
