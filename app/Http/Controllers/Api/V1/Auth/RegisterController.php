<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\Auth\Auth;

class RegisterController extends Controller
{
    public function index(RegisterRequest $request, Auth $auth)
    {
        $user = $auth->register($request);
        $token = $user->createToken('user-register');

        return $user->toResource(UserResource::class)->additional(['token' => $token->plainTextToken]);
    }
}
