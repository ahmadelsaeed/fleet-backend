<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OAT;


class LoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $credentials = $request->validate(['login' => ['required'], 'password' => ['required', 'max:100'],]);

        $login_type = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($login_type, $credentials['login'])->first();
        if (!$user) {
            return $this->respondWithError(trans('Invalid credentials'));
        }

        $checkPass = Hash::check($credentials['password'], $user->password);

        if (! $user || ! $checkPass) {
            return $this->respondWithError(trans('Invalid credentials'));
        }

        $token = $user->createToken()->plainTextToken;;

        return $this->respondWithSuccess(__('Login Successfully'), ['user' => new UserResource($user),'token' => $token]);
    }
}
