<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'date' => $this->date->toDateString(),
            'bus' => [
                'id' => $this->bus->id,
                'plate_number' => $this->bus->plate_number,
                'seats_count' => $this->bus->seats_count,
            ],
            'trip_stops' => TripStopResource::collection($this->whenLoaded('tripStops')),
        ];
    }
}
