<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip' => new TripResource($this->whenLoaded('trip')),
            'seat' => [
                'id' => $this->seat->id ?? null,
                'seat_number' => $this->seat->seat_number ?? null,
            ],
            'start_stop' => $this->when(
                $this->relationLoaded('startStop'),
                fn () => [
                    'id' => $this->startStop->id,
                    'sequence_order' => $this->startStop->sequence_order,
                    'station' => new StationResource($this->startStop->station),
                ]
            ),
            'end_stop' => $this->when(
                $this->relationLoaded('endStop'),
                fn () => [
                    'id' => $this->endStop->id,
                    'sequence_order' => $this->endStop->sequence_order,
                    'station' => new StationResource($this->endStop->station),
                ]
            ),
            'created_at' => $this->created_at,
        ];
    }
}
