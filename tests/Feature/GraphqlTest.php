<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Nuwave\Lighthouse\Testing\RefreshesSchemaCache;
use App\Models\Student;

use function PHPUnit\Framework\assertTrue;

class GraphqlTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;
    use MakesGraphQLRequests;
    use RefreshesSchemaCache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootRefreshesSchemaCache();
    }

    private function getToken()
    {
        \App\Models\User::factory()->create([
            'name' => 'a',
            'email' => 'a@a',
            'password' => Hash::make('a'),
        ]);

        return $this->post(route('login'), [
            'email' => 'a@a',
            'password' => 'a',
        ])['access_token'];
    }
    
    /**
     * @depends Tests\Feature\LoginTest::testLoginOk
     */
    public function testQueryStudentsNeedsAuth ()
    {
        $response = $this->graphQL(/** @lang GraphQL */'
            query {
                students {
                    id
                }
            }', [], [], ['Accept' => 'application/json']);
        
        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    /**
     * @depends Tests\Feature\LoginTest::testLoginOk
     */
    public function testQueryStudentsAuthWorks ()
    {
        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            query {
                students {
                    id
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);
        
        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorFree();
    }

    /**
     * @depends testQueryStudentsAuthWorks
     */
    public function testQueryStudentsOk ()
    {
        Student::factory(5)->create();

        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            query {
                students {
                    id
                    firstname
                    lastname
                    email
                    address
                    score
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorFree();

        $this->assertEquals(5, count($response['data']['students']));

        $response->assertJsonStructure([
            'data' => [
                'students' => [
                    [
                        'id',
                        'firstname',
                        'lastname',
                        'email',
                        'address',
                        'score',
                    ],
                ],
            ],
        ]);
    }
}
