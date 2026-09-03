<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\OpenApi\Operations\Profile\Me;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    #[Me]
    public function me(Request $request)
    {
        return $this->respondWithSuccess(__('User profile retrieved successfully'), ['user' => new UserResource($request->user())]);
    }
}
