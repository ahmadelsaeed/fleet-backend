<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
            'seat_id' => ['required', 'integer', 'exists:seats,id'],
            'start_station_id' => ['required', 'integer', 'exists:stations,id'],
            'end_station_id' => ['required', 'integer', 'exists:stations,id'],
        ];
    }
}
