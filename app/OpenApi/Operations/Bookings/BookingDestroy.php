<?php

namespace App\OpenApi\Operations\Bookings;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\EmptyData;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BookingDestroy extends OA\Delete
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/bookings/{booking}',
            operationId: 'bookingsDestroy',
            summary: 'Cancel a booking',
            tags: ['Bookings'],
            parameters: [
                new OA\PathParameter(name: 'booking', description: 'Booking id', required: true, schema: new OA\Schema(type: 'integer')),
            ],
            responses: [
                new SuccessResponse(
                    description: 'Booking cancelled successfully',
                    messageExample: 'Booking cancelled successfully',
                    data: EmptyData::class,
                ),
                new ErrorResponse(
                    description: 'Forbidden',
                    messageExample: 'Forbidden',
                    response: 403,
                ),
            ],
        );
    }
}
