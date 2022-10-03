<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    private AuthService $authService;

    /**
     * Constructor
     * Injecting authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @param LoginUserRequest $request
     * @return array - ['access_token', 'token_type' => 'Bearer'] the access token
     * @throws UnauthorizedHttpException if email or password are incorrect
     */
    public function login(LoginUserRequest $request) 
    {
        return [
            'access_token' => $this->authService->login($request), 
            'token_type' => 'Bearer',
        ];
    }

    /**
     * @return array ['message' => 'successfully log out']
     * @status_code HTTP_OK
     * @throws UnauthorizedHttpException if access token is invalid
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request);
        return response(['message' => 'successfully log out'], Response::HTTP_OK);
    }
}
