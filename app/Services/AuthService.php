<?php

namespace App\Services;

use App\Http\Requests\LoginUserRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

    /**
     * @param LoginUserRequest $request
     * @return string $accessToken the login access token of the user
     * @throws UnauthorizedHttpException if email or password are incorrect
     */
    public function login(LoginUserRequest $loginUserRequest) 
    {
        if (!Auth::attempt($loginUserRequest->only(['email', 'password'])))
            throw new UnauthorizedHttpException('Invalid username or password');

        return $this->findOneByEmail($loginUserRequest->email)->createToken('accessToken')->plainTextToken;
    }

    /**
     * Removes the access token if valid
     * @throws UnauthorizedHttpException if access token is invalid or there is no access token
     */
    public function logout(Request $request)
    {
        $accessToken = PersonalAccessToken::findToken($request->bearerToken());
        
        $accessToken->delete();
    }
}