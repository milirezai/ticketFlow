<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class RegisterController extends Controller
{
    public function index(RegisterRequest $request)
    {
        $inputs = $request->all();
        if ($request->hasFile('profile_photo_path')){
            $file = $request->file('profile_photo_path');
            $name = time().'.'.$file->getClientOriginalExtension();
            $save = $file->move(public_path('image/profile'),$name);
            $filePath = 'image/profile/'.$name;
            $inputs['profile_photo_path'] = $filePath;
        }
        $password = Hash::make($inputs['password']);
        $inputs['activation'] = 0;
        $inputs['status'] = 1;
        $user = User::create($inputs);
        $token = $user->createToken('user-register');

        return $user->toResource(UserResource::class)->additional(['token' => $token->plainTextToken]);
    }
}
