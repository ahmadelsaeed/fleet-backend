<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BookingsList',
    required: ['bookings'],
    properties: [
        new OA\Property(property: 'bookings', type: 'array', items: new OA\Items(ref: Booking::class)),
    ],
    type: 'object'
)]
final class BookingsList {}
