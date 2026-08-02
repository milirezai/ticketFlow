<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function index(LoginRequest $request)
    {
        $user = User::where('email',$request->input('email'))->first();
        if (!Hash::check($request->password,$user->password))
            throw new AuthenticationException();
        $user->tokens()->delete();
        $token = $user->createToken('api-token');

        return $user->toResource(UserResource::class)->additional(['token' => $token->plainTextToken]);
    }
}
