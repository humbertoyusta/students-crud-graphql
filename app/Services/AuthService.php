<?php

namespace App\Services;

use App\Http\Requests\LoginUserRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthService 
{
    /**
     * @param $email
     * @return the user with given $email or null if it doesn't exists
     */
    public function findOneByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    public function login(LoginUserRequest $loginUserRequest) 
    {
        if (!Auth::attempt($loginUserRequest->only(['email', 'password'])))
            throw new UnauthorizedHttpException('Invalid username or password');

        return $this->findOneByEmail($loginUserRequest->email)->createToken('accessToken')->plainTextToken;
    }
}