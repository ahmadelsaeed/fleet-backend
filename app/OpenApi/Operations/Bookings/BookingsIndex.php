<?php

namespace App\OpenApi\Operations\Bookings;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\BookingsList;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BookingsIndex extends OA\Get
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/bookings',
            operationId: 'bookingsIndex',
            summary: 'List user bookings',
            tags: ['Bookings'],
            security: [
                ['sanctum' => []],
            ],
            responses: [
                new SuccessResponse(
                    description: 'Bookings retrieved successfully',
                    messageExample: 'Bookings retrieved successfully',
                    data: BookingsList::class,
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
