<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\Auth\Auth;

class LoginController extends Controller
{
    public function index(LoginRequest $request, Auth $auth)
    {
         $user = $auth->login($request->all());
         $token = $user->createToken('auth-login');

        return $user->toResource(UserResource::class)
            ->additional(['token' => $token->plainTextToken]);
    }
}
