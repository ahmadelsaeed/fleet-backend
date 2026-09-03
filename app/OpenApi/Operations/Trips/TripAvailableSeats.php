<?php

namespace App\OpenApi\Operations\Trips;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\SeatsAvailabilityResponse;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class TripAvailableSeats extends OA\Get
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/trips/{trip}/available-seats',
            operationId: 'tripsAvailableSeats',
            summary: 'Get available seats for a trip between two stations',
            tags: ['Trips'],
            parameters: [
                new OA\PathParameter(name: 'trip', description: 'Trip id', required: true, schema: new OA\Schema(type: 'integer')),
                new OA\QueryParameter(name: 'start_station_id', description: 'Start station id', required: true, schema: new OA\Schema(type: 'integer')),
                new OA\QueryParameter(name: 'end_station_id', description: 'End station id', required: true, schema: new OA\Schema(type: 'integer')),
            ],
            responses: [
                new SuccessResponse(
                    description: 'Seat availability retrieved successfully',
                    messageExample: 'Seat availability retrieved successfully',
                    data: SeatsAvailabilityResponse::class,
                ),
                new ErrorResponse(
                    description: 'Invalid request',
                    messageExample: 'Invalid request',
                ),
            ],
        );
    }
}
