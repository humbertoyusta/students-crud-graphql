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

    /**
     * @return $token the authorization token of a newly created user
     */
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
     * Tests that you can not access to query students without auth
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
     * Tests that you can access to query students with auth
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
     * Tests that query students returns all students with correct properties
     * @depends testQueryStudentsAuthWorks
     */
    public function testQueryStudentsOk ()
    {
        // add 5 student to the database
        Student::factory(5)->create();

        $token = $this->getToken();

        //make a query to get all students and all properties
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
     * Tests that the proper error is given after making a query with wrong properties
     * @depends testQueryStudentsAuthWorks
     */
    public function testQueryStudentsWrongSchema ()
    {
        // add one student to the database
        Student::factory(1)->create();

        $token = $this->getToken();

        // make a wrong query (should be created_at instead of createdAt)
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
        // add one student to the database
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

    /**
     * Tests that a student can be correctly created
     */
    public function testCreateStudentOk ()
    {
        $token = $this->getToken();

        // make a mutation query to create a student with all the data
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

    /**
     * Tests that the proper error is sent if the information is incomplete
     */
    public function testCreateStudentIncomplete ()
    {
        $token = $this->getToken();

        // make a mutation query without email address
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

    /**
     * Tests that a field can be updated correctly
     */
    public function testUpdateStudentOk ()
    {
        $token = $this->getToken();

        // add a student to the database
        Student::create([
            'firstname' => "a",
            'lastname' => "b",
            'email' => 'c@d',
            'address' => "e",
            'score' => 5,
        ]);

        // update the firstname of the first student
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

    /**
     * Tests that if a student email is updated to another email that is taken by another
     * student it throws the proper error
     */
    public function testUpdateStudentEmailConflict ()
    {
        $token = $this->getToken();

        // Create a first student
        Student::create([
            'firstname' => "a",
            'lastname' => "b",
            'email' => 'c@d',
            'address' => "e",
            'score' => 5,
        ]);

        // Create a second student
        Student::create([
            'firstname' => "a",
            'lastname' => "b",
            'email' => 'f@f',
            'address' => "e",
            'score' => 5,
        ]);

        // Try to update the second student and change its email to
        // the email of the first student
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

    /**
     * Tests that if the email of an user is changed to that same email
     * does not throw any error
     */
    public function testUpdateStudentSameEmailOk ()
    {
        $token = $this->getToken();

        // Create a student
        Student::create([
            'firstname' => 'a',
            'lastname' => 'b',
            'email' => 'c@d',
            'address' => 'e',
            'score' => 5,
        ]);

        // Change the email of the first user to that same email
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

    /**
     * Tests that if a student that does not exist is trying ot be updated
     * the proper error is thrown
     */
    public function testUpdateStudentNotFound ()
    {
        $token = $this->getToken();

        // try to update a non existing user
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

    /**
     * Test that students can be deleted properly
     */
    public function testDeleteStudentOk ()
    {
        $token = $this->getToken();

        // Create 5 students
        Student::factory(5)->create();

        for ($id = 1; $id <= 5; $id ++)
        {
            // delete the i-th student
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

        // assert that all users were deleted and the database is empty
        $this->assertEquals(count(Student::all()), 0);
    }

    /**
     * Tests that if a user is going to be deleted but it does not exist
     * the proper error is thrown
     */
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
