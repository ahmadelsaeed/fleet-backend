<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StationResource;
use App\Models\Station;
use App\OpenApi\Operations\Stations\StationsIndex;
use Illuminate\Http\JsonResponse;

class StationController extends Controller
{
    /**
     * GET /stations — list all stations.
     * Public endpoint, no authentication required.
     */
    #[StationsIndex]
    public function index(): JsonResponse
    {
        $stations = Station::orderBy('name')->get();

        return $this->respondWithSuccess(
            __('Stations retrieved successfully'),
            ['stations' => StationResource::collection($stations)]
        );
    }
}
