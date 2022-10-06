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
                    created_at
                    updated_at
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
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @depends testQueryStudentsAuthWorks
     */
    public function testQueryStudentsWrongSchema ()
    {
        Student::factory(1)->create();

        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            query {
                students {
                    createdAt
                    updatedAt
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorCategory('graphql');
    }

    /**
     * @depends testQueryStudentsAuthWorks
     */
    public function testQueryStudentOk ()
    {
        Student::factory(1)->create();

        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            query {
                student(id: 1) {
                    id
                    firstname
                    lastname
                    email
                    address
                    score
                    created_at
                    updated_at
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorFree();

        $response->assertJsonStructure([
            'data' => [
                'student' => [
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'address',
                    'score',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

        $this->assertEquals(1, $response['data']['student']['id']);
    }

    /**
     * @depends testQueryStudentsAuthWorks
     */
    public function testQueryStudentNotFound ()
    {
        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            query {
                student(id: 1) {
                    id
                    firstname
                    lastname
                    email
                    address
                    score
                    created_at
                    updated_at
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorCategory('custom');
        $response->assertGraphQLErrorMessage('Not Found (404)');
    }

    public function testCreateStudentOk ()
    {

        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                createStudent(
                    firstname: "a",
                    lastname: "b",
                    email: "c@d",
                    address: "e",
                    score: 5,
                ) {
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

        $response->assertExactJson([
            'data' => [
                'createStudent' => [
                    'id' => '1',
                    'firstname' => 'a',
                    'lastname' => 'b',
                    'email' => 'c@d',
                    'address' => 'e',
                    'score' => 5,
                ]
            ]
        ]);
    }

    public function testCreateStudentIncomplete ()
    {
        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                createStudent(
                    firstname: "a",
                    lastname: "b",
                    address: "e",
                    score: 5,
                ) {
                    id
                    firstname
                    lastname
                    address
                    score
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);
        
        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorCategory('graphql');
    }

    public function testUpdateStudentOk ()
    {
        $token = $this->getToken();

        Student::create([
            'firstname' => "a",
            'lastname' => "b",
            'email' => 'c@d',
            'address' => "e",
            'score' => 5,
        ]);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                updateStudent(
                    id: "1",
                    firstname: "f",
                ) {
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

        $response->assertExactJson([
            'data' => [
                'updateStudent' => [
                    'id' => '1',
                    'firstname' => 'f',
                    'lastname' => 'b',
                     'email' => 'c@d',
                    'address' => 'e',
                    'score' => 5,
                 ]
            ]
        ]);
    }

    public function testUpdateStudentEmailConflict ()
    {
        $token = $this->getToken();

        Student::create([
            'firstname' => "a",
            'lastname' => "b",
            'email' => 'c@d',
            'address' => "e",
            'score' => 5,
        ]);

        Student::create([
            'firstname' => "a",
            'lastname' => "b",
            'email' => 'f@f',
            'address' => "e",
            'score' => 5,
        ]);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                updateStudent(
                    id: "2",
                    email: "c@d",
                ) {
                    email
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorCategory('custom');
        $response->assertGraphQLErrorMessage('Conflict (409)');
    }


    public function testUpdateStudentSameEmailOk ()
    {
        $token = $this->getToken();

        Student::create([
            'firstname' => 'a',
            'lastname' => 'b',
            'email' => 'c@d',
            'address' => 'e',
            'score' => 5,
        ]);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                updateStudent(
                    id: "1",
                    email: "c@d",
                ) {
                    email
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorFree();

        $this->assertEquals($response['data']['updateStudent']['email'], "c@d");
    }

    public function testUpdateStudentNotFound ()
    {
        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                updateStudent(
                    id: "1",
                    email: "c@d",
                ) {
                    email
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorCategory('custom');
        $response->assertGraphQLErrorMessage('Not Found (404)');
    }

    public function testDeleteStudentOk ()
    {
        $token = $this->getToken();

        Student::factory(5)->create();

        for ($id = 1; $id <= 5; $id ++)
        {
            $response = $this->graphQL(/** @lang GraphQL */'
                mutation ($id: ID!) {
                    deleteStudent(id: $id) {
                        id
                    }
                }', ['id' => $id], [], ['Authentication' => 'Bearer '.$token, 
                'Accept' => 'application/json']);

            $response->assertStatus(Response::HTTP_OK);

            $response->assertGraphQLErrorFree();

            $this->assertEquals($id, $response['data']['deleteStudent']['id']);
        }

        $this->assertEquals(count(Student::all()), 0);
    }

    public function testDeleteStudentNotFound()
    {
        $token = $this->getToken();

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                deleteStudent(id: 1) {
                    id
                }
            }', [], [], ['Authentication' => 'Bearer '.$token, 
            'Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertGraphQLErrorCategory('custom');
        $response->assertGraphQLErrorMessage('Not Found (404)');
    }
}
