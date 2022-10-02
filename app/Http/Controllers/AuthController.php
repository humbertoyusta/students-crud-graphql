<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

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

    public function login(LoginUserRequest $request) 
    {
        return [
            'access_token' => $this->authService->login($request), 
            'token_type' => 'Bearer',
        ];
    }
}
