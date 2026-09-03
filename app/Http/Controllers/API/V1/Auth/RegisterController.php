<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\OpenApi\Operations\Auth\Register;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    #[Register]
    public function __invoke(AddUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create($data);

        $token = $user->createToken()->plainTextToken;

        return $this->respondWithSuccess(__('Login Successfully'), ['user' => new UserResource($user), 'token' => $token]);
    }
}
