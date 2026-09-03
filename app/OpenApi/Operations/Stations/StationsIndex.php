<?php

namespace App\OpenApi\Operations\Stations;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\StationsList;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class StationsIndex extends OA\Get
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/stations',
            operationId: 'stationsIndex',
            summary: 'List stations',
            tags: ['Stations'],
            security: [
                ['sanctum' => []],
            ],
            responses: [
                new SuccessResponse(
                    description: 'Stations retrieved successfully',
                    messageExample: 'Stations retrieved successfully',
                    data: StationsList::class,
                ),
                new ErrorResponse(
                    description: 'Request failed',
                    messageExample: 'Request failed',
                ),
            ],
        );
    }
}
