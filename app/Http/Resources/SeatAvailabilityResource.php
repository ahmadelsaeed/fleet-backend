<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a Seat model that has had is_available set by SeatAvailabilityService.
 */
class SeatAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'seat_id' => $this->id,
            'seat_number' => $this->seat_number,
            'is_available' => (bool) $this->is_available,
        ];
    }
}
