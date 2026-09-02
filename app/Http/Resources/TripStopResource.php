<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sequence_order' => $this->sequence_order,
            'station' => new StationResource($this->whenLoaded('station')),
        ];
    }
}
