<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;

class LogoutController extends Controller
{
    public function __invoke()
    {
        auth()->user()->tokens()->delete();

        return $this->respondWithSuccess(__('Logged out successfully'));
    }
}
