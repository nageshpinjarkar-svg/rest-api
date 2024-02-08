<?php

namespace AppBundle\Tests\Controller\API;
use AppBundle\Test\ApiTestCase;
use AppBundle\Test\ResponseAsserter;

class ProgrammerControllerTest extends ApiTestCase
{
	// create user 
	protected function setUp()
	{
		parent::setUp(); 
		$this->createUser('weaverryan');
	}


	public function testPOST()
	{
		$nickname = 'ObjectOrienter'.rand(0, 999); 
		$data = array(
			'nickname' => 'ObjectOrienter', 
			'avatarNumber' => 5,
			'tagLine' => 'a test dev!'
		);
        // Extend the base class ApitestCase
        $response = $this->client->post('/api/programmers', [ 
			'body' => json_encode($data)
		]);
		
		// assert... test a value 
		// assertEquals: Reports an error identified by $message if the two parameters are not equal. 
		// if assertEquals don't work use assertSame() 
		// https://stackoverflow.com/questions/10254180/difference-between-assertequals-and-assertsame-in-phpunit/10254238
		$this->assertSame(201, $response->getStatusCode());
		// fail
		// $this->assertSame('/api/programmers/ObjectOrienter', $response->getHeader('Location'));
		// work
		$this->assertStringEndsWith('/api/programmers/ObjectOrienter', $response->getHeader('Location'));
		$this->assertTrue($response->hasHeader('Location'));
		$finishedData = json_decode($response->getBody(true), true);
		$this->assertArrayHasKey('nickname', $finishedData);
		$this->assertSame('ObjectOrienter', $finishedData['nickname']);
	}

	// test one ressources
	public function testGETProgrammer()
	{
		// before we make a request to fetch a single programmer, we need to make sure there's one in the database !
		$this->createProgrammer(array( 
			'nickname' => 'UnitTester', 
			'avatarNumber' => 3,
		));

		$response = $this->client->get('/api/programmers/UnitTester');
		// assertequals & assertSame work both
		$this->assertSame(200, $response->getStatusCode());
		$this->asserter()->assertResponsePropertiesExist($response, array(
				'nickname', 
				'avatarNumber', 
				'powerLevel', 
				'tagLine'
		));
		// $this->asserter()->assertResponsePropertyEquals($response, 'nickname', 'UnitTester');
		// because assertEquals do not work well I create assertResponsePropertySame that do the same work !
		$this->asserter()->assertResponsePropertySame($response, 'nickname', 'UnitTester');
		//debug the response
		// $this->debugResponse($response);
		// add a new assert that checks that we have a uri property that's equal to /api/programmers/UnitTester
		
		// change uri to _links.self, the key self is a name used when linking to, your, "self"
		$this->asserter()->assertResponsePropertySame(
			$response, 
			'_links.self', 
			$this->adjustUri('/api/programmers/UnitTester')
		);
	}

	// deep deep
	// The idea is simple: if the client adds ?deep=1 , then the API should expose more embedded objects
	public function testGETProgrammerDeep() {
		$this->createProgrammer(array( 
			'nickname' => 'UnitTester', 
			'avatarNumber' => 3,
		));
		$response = $this->client->get('/api/programmers/UnitTester?deep=1');
		$this->assertEquals(200, $response->getStatusCode());
		// test the other obj : the user 
		$this->asserter()->assertResponsePropertiesExist($response, array( 'user.username'));
	}

	// testing the GET collection 
	public function testGETProgrammersCollection() 
	{
		$this->createProgrammer(array( 
			'nickname' => 'UnitTester', 
			'avatarNumber' => 3,
		)); 
		$this->createProgrammer(array(
			'nickname' => 'CowboyCoder',
			'avatarNumber' => 5, 
		));
		// the request
		$response = $this->client->get('/api/programmers');
		// show the url
		// $this->printLastRequestUrl();
		// the assert
		$this->assertSame(200, $response->getStatusCode());
		// because listAction return an associative array with a programmers (the collection of programmers)
		// let's first assert that there's a programmers key in the response and that it's an array.
		$this->asserter()->assertResponsePropertyIsArray($response, 'items');
		// next, let's assert that there are two things on this array
		$this->asserter()->assertResponsePropertyCount($response, 'items', 2);
		$this->asserter()->assertResponsePropertySame($response, 'items[1].nickname', 'CowboyCoder');
		// to test it use this commande with --filter and fucntion name :
		// php bin/phpunit -c app --filter testGETProgrammersCollection src/AppBundle/Tests/Controller/Api/ProgrammerControllerTest.php
	}

	// pagination  
	public function testGETProgrammersCollectionPaginated()
	{
		// new programmer 
		$this->createProgrammer(array( 
			'nickname' => 'willnotmatch', 
			'avatarNumber' => 5,
		));

		// create 25 programmers
		for ($i = 0; $i < 25; $i++) { 
			$this->createProgrammer(array(
				'nickname' => 'Programmer'.$i,
				'avatarNumber' => 3, 
			));
		}

		// response with filter
		$response = $this->client->get('/api/programmers?filter=programmer');

		$this->assertSame(200, $response->getStatusCode());
		// assert that the programmer with index 5 is equal to Programmer5 :
		$this->asserter()->assertResponsePropertySame(
			$response, 
			'items[5].nickname', 
			'Programmer5'
		);
		//  how many results are on this page
		$this->asserter()->assertResponsePropertySame(
			$response, 
			'count', 
			10
		);
		// how many results there are in total
		$this->asserter()->assertResponsePropertySame(
			$response, 
			'total', 
			25
		);
		// link correspond to next link whose value will be the URL to get the next page of results
		$this->asserter()->assertResponsePropertyExists(
			$response, 
			'_links.next'
		);
		// we need to make a request to page 2 and make sure we see the next 10 programmers
		// we can read the next link and use that for the next request like clicking links
		$nextLink = $this->asserter()->readResponseProperty($response, '_links.next');
		$response = $this->client->get($nextLink);

		// test the next page
		$this->asserter()->assertResponsePropertySame(
			$response, 
			'items[5].nickname', 
			'Programmer15'
		);
		//  how many results are on this page
		$this->asserter()->assertResponsePropertySame(
			$response, 
			'count', 
			10
		);
		// test the last link
		$lastLink = $this->asserter()->readResponseProperty($response, '_links.last');
		$response = $this->client->get($lastLink);

		$this->asserter()->assertResponsePropertySame(
			$response, 
			'items[4].nickname', 
			'Programmer24'
		);
		// make sure that there is no programmer here with index 5
		$this->asserter()->assertResponsePropertyDoesNotExist(
			$response, 
			'items[5].nickname'
		);

		$this->asserter()->assertResponsePropertySame(
			$response, 
			'count', 
			5
		);
	}

	public function testPUTProgrammer()
	{
		$this->createProgrammer(array(
			'nickname' => 'CowboyCoder',
			'avatarNumber' => 5,
			'tagLine' => 'foo'
		));
		// new data to update
		$data = array(
			'nickname' => 'CowgirlCoder', 
			'avatarNumber' => 2,
			'tagLine' => 'foo'
		);
		// request 
		$response = $this->client->put('/api/programmers/CowboyCoder', [
			'body' => json_encode($data) 
		]);
		// verif
		$this->assertSame(200, $response->getStatusCode());
		$this->asserter()->assertResponsePropertySame($response, 'avatarNumber', 2);
		// the nickname is immutable on edit
        $this->asserter()->assertResponsePropertySame($response, 'nickname', 'CowboyCoder');
	}
	// use patch when you want edit a resource parameter
	public function testPATCHProgrammer()
	{
		$this->createProgrammer(array( 
			'nickname' => 'CowboyCoder', 
			'avatarNumber' => 5, 
			'tagLine' => 'foo',
		));
		$data = array( 
			'tagLine' => 'bar',
		);
		// method : PATCH
		$response = $this->client->patch('/api/programmers/CowboyCoder', [
			'body' => json_encode($data) 
		]);
		$this->assertSame(200, $response->getStatusCode()); 
		$this->asserter()->assertResponsePropertySame($response, 'avatarNumber', 5);
		$this->asserter()->assertResponsePropertySame($response, 'tagLine', 'bar');

	}

	public function testDELETEProgrammer()
	{
		$this->createProgrammer(array( 
			'nickname' => 'UnitTester', 
			'avatarNumber' => 3,
		));
		// no need to return ressources
		$response = $this->client->delete('/api/programmers/UnitTester');
		// statut code : 204
		$this->assertSame(204, $response->getStatusCode());

	}
	// 1) Test for a Required Username
	public function testValidationErrors()
	{
		$data = array(
			'avatarNumber' => 2,
			'tagLine' => 'bla bla bla'
		);
        $response = $this->client->post('/api/programmers', [ 
			'body' => json_encode($data)
		]);
		// because we don't return nickname we use 400 statut code
		$this->assertSame(400, $response->getStatusCode());
		// Validation Errors Response Body : 
		// with assertResponsePropertiesExist() response have 3 prop : type, title, errors
		$this->asserter()->assertResponsePropertiesExist($response, array( 
			'type',
			'title',
			'errors', 
		));
		// test error validation on nickname
		// we use assertResponsePropertyExists for one field 
		// we use assertResponsePropertiesExist() for many fields
		$this->asserter()->assertResponsePropertyExists($response, 'errors.nickname');
		// assert the exact validation message.
		$this->asserter()->assertResponsePropertySame(
			$response, 
			'errors.nickname[0]', 
			'Please enter a clever nickname'
		);
		// test avatarNumber
		$this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.avatarNumber');
		// test the response header
		$this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
	}
	// test invalid json
	public function testInvalidJson()
    {
        $invalidBody = <<<EOF
{
    "nickname": "JohnnyRobot",
    "avatarNumber" : "2
    "tagLine": "I'm from a test!"
}
EOF;

        $response = $this->client->post('/api/programmers', [
            'body' => $invalidBody
        ]);

        // $this->debugResponse($response);
        $this->assertSame(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyContains($response, 'type', 'invalid_body_format');
    }
    // test 404
    public function test404Exception()
    {
    	$response = $this->client->get('/api/programmers/fake');
    	$this->assertSame(404, $response->getStatusCode());
		$this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
		// the 404 status code already says everything we need to
		// Under "Pre-Defined Problem Types", it says that if the status code is enough, 
		// you can set type to about:blank 
		$this->asserter()->assertResponsePropertySame($response, 'type', 'about:blank');
		// set title to whatever the standard text is for that status code. A 404 would be "Not Found".
		$this->asserter()->assertResponsePropertySame($response, 'title', 'Not Found');
		// introducing the detail Property
		$this->asserter()->assertResponsePropertySame($response, 'detail', 'No programmer found with nickname "fake"');
    }
    // test authentication
    public function testRequiresAuthentication()
    {
    	$response = $this->client->post('/api/programmers', [ 
    		'body' => '[]'
		]);
		$this->assertSame(401, $response->getStatusCode());
    }
}