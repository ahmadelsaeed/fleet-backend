<?php

namespace App\OpenApi\Operations\Bookings;

use App\OpenApi\Responses\ErrorResponse;
use App\OpenApi\Responses\SuccessResponse;
use App\OpenApi\Schemas\Booking;
use App\OpenApi\Schemas\BookingStoreRequest;
use OpenApi\Attributes as OA;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BookingStore extends OA\Post
{
    public function __construct()
    {
        parent::__construct(
            path: '/api/v1/bookings',
            operationId: 'bookingsStore',
            summary: 'Create a booking',
            tags: ['Bookings'],
            security: [
                ['sanctum' => []],
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: BookingStoreRequest::class),
            ),
            responses: [
                new SuccessResponse(
                    description: 'Booking created successfully',
                    messageExample: 'Booking created successfully',
                    data: Booking::class,
                    response: 201,
                ),
                new ErrorResponse(
                    description: 'Conflict - seat taken',
                    messageExample: 'Seat already taken',
                    response: 409,
                ),
            ],
        );
    }
}
