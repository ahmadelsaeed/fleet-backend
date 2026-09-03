<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\OpenApi\Operations\Auth\Logout;

class LogoutController extends Controller
{
    #[Logout]
    public function __invoke()
    {
        auth()->user()->tokens()->delete();

        return $this->respondWithSuccess(__('Logged out successfully'));
    }
}
