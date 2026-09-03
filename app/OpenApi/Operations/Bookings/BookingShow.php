<?php

namespace App\OpenApi\Operations\Bookings;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\Booking;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BookingShow extends OA\Get
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/bookings/{booking}',
            operationId: 'bookingsShow',
            summary: 'Get booking details',
            tags: ['Bookings'],
            parameters: [
                new OA\PathParameter(name: 'booking', description: 'Booking id', required: true, schema: new OA\Schema(type: 'integer')),
            ],
            responses: [
                new SuccessResponse(
                    description: 'Booking retrieved successfully',
                    messageExample: 'Booking retrieved successfully',
                    data: Booking::class,
                ),
                new ErrorResponse(
                    description: 'Forbidden or not found',
                    messageExample: 'Forbidden',
                    response: 403,
                ),
            ],
        );
    }
}
