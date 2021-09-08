<?php

namespace tests\Object;

use OObject;
use tests\controllers\cTestController;
use tests\controllers\Nested\cNestedController;
use tests\TestCase;

/**
 * @covers OObject
 */
class CreateObjectTest extends TestCase
{
	public function test404()
	{
		$response = $this->router->route('tests/DoesNotExist');

		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertObjectNotHasAttribute('data', $response);
		$this->assertTrue($response->isError());
		$this->assertObjectHasAttribute('errors', $response);
		$this->assertJsonStringEqualsJsonString(json_encode([
			'notfound' => [
				'Route not found object: /DoesNotExist',
			]
		]), json_encode($response->errors));
	}

	public function testCreateAnObject()
	{
		$response = $this->router->route('tests/TestController');

		$this->assertNotError();
		$this->assertInstanceOf(cTestController::class, $response);
		$this->assertNotSame($this->router, $response);
		$this->assertObjectNotHasAttribute('data', $response);
	}

	public function testBlank()
	{
		$response = $this->router->route('');

		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertObjectNotHasAttribute('data', $response);
		$this->assertTrue($response->isError());
		$this->assertObjectHasAttribute('errors', $response);
		$this->assertJsonStringEqualsJsonString(json_encode([
			'notfound' => [
				'Route not found object: /index',
			]
		]), json_encode($response->errors));
	}

	public function testIndexOfController()
	{
		$response = $this->router->route('tests/Nested/NestedController');

		$this->assertInstanceOf(cNestedController::class, $response);
		$this->assertObjectNotHasAttribute('data', $response);
		$this->assertFalse($response->isError());
		$this->assertObjectNotHasAttribute('errors', $response);
	}
}
