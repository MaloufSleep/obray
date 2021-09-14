<?php

namespace tests\Object;

use App\controllers\cNoIndex;
use tests\TestCase;

/**
 * @covers \OObject
 */
class ExecuteMethodTest extends TestCase
{
	public function testExceptionInController()
	{
		$response = $this->router->route('app/TestController/throwException');

		$this->assertNotSame($this->router, $response);
		$this->assertError($response);
		$this->assertJsonStringEqualsJsonString(json_encode([
			'general' => [
				'Expected exception thrown',
			],
		]), json_encode($response->errors));
	}

	public function testExceptionIndexInController()
	{
		$response = $this->router->route('app/TestController/doesNotExist');

		$this->assertNotSame($this->router, $response);
		$this->assertError($response);
		$this->assertJsonStringEqualsJsonString(json_encode([
			'general' => [
				'Expected exception thrown in index',
			],
		]), json_encode($response->errors));
	}

	public function testMethodDoesNotExist()
	{
		$response = $this->router->route('app/NoIndex/doesNotExist');

		$this->assertNotSame($this->router, $response);
		$this->assertInstanceOf(cNoIndex::class, $response);
		$this->assertNotError($response);
		$this->assertObjectNotHasAttribute('data', $response);
	}
}
