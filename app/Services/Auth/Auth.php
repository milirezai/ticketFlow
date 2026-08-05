<?php

namespace App\Services\Auth;

use App\Models\User\User;
use App\Services\Auth\Contracts\AuthContract;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Auth implements AuthContract
{

    public function register(FormRequest $request): User
    {
        $inputs = $request->all();
        if ($request->hasFile('profile_photo_path')){
            $file = $request->file('profile_photo_path');
            $name = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('image/profile'),$name);
            $filePath = 'image/profile/'.$name;
            $inputs['profile_photo_path'] = $filePath;
        }
        $inputs['password'] = Hash::make($inputs['password']);
        $inputs['activation'] = 0;
        $inputs['status'] = 1;
        $user = User::create($inputs);

        return $user;
    }

    public function login(FormRequest $request): User
    {
        $user = User::where('email', $request->email)->first();
        if (!Hash::check($request->password,$user->password))
            throw new AuthenticationException();

        $user->tokens()->delete();

        return $user;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function changePassword(User $user, FormRequest $request): User
    {
        if (Hash::check($request->old_password,$user->password)){
            $user->update(['password' => Hash::make($request->password)]);
            $user->currentAccessToken()->delete();
            return $user;
        }
        throw new AuthenticationException();
    }

    public function forgotPassword(FormRequest $request): void
    {
        $user = User::where('email', $request->email)->first();
        if ($user){
            // send token
        }
        else
            throw new AuthenticationException();
    }

    public function resetPassword(FormRequest $request): bool
    {
        $user = User::where('email',$request->email)->first();
        /*
        $otp = $otp->verify($user, $request->otp);
        if ($otp){
            $user->update([
                'password' => Hash::make($request->password)
            ]);
            return true;
        }
        else{
            return false;
        }
        */
    }
}


