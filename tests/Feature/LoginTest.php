<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

use function PHPUnit\Framework\assertTrue;

class LoginTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    private array $logInUser = [
        'email' => 'a@a',
        'password' => 'a',
    ];

    /**
     * set up
     * creates a user to the database
     */
    public function setUp(): void
    {
        parent::setUp();

        $dbUser = [
            'name' => 'a',
            'email' => 'a@a',
            'password' => Hash::make('a'),
        ];

        \App\Models\User::factory()->create($dbUser);
    }

    /**
     * Tests that it is possible to lig in and get a token
     */
    public function testLoginOk()
    {
        $response = $this->post(route('login'), $this->logInUser);

        $response->assertStatus(Response::HTTP_CREATED);

        $response->assertJsonStructure(['access_token', 'token_type']);
    }

    /**
     * Tests that if you log in with wrong password HTTP_UNAUTHORIZED is thrown
     */
    public function testLoginWrongPassword()
    {
        $response = $this->post(route('login'), [
            'email' => $this->logInUser['email'], 
            'password' => $this->logInUser['password'].'bad', 
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Tests that it is possible to log out correctly
     * @depends testLoginOk
     */
    public function testLogoutOk()
    {
        $token = $this->post(route('login'), $this->logInUser)['access_token'];

        $response = $this->post(route('logout'), [], ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertExactJson(['message' => 'successfully log out']);
    }

    /**
     * Tests that if you try to logout without token HTTP_UNAUTHORIZED is thrown
     * @depends testLoginOk
     */
    public function testLogoutInvalidToken()
    {

        $response = $this->post(route('logout'), [], ['Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
