<?php

namespace tests\Object;

use OObject;
use tests\TestCase;

class RouteTest extends TestCase
{
	/**
	 * @covers OObject::route
	 */
	public function testSuccessfulRoute()
	{
		$response = $this->router->route('tests/TestController/test');

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertNotSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertSame([
			'Success message',
		], $response->data);
	}

	/**
	 * @covers OObject::route
	 */
	public function testRouteWithQuery()
	{
		// Array body
		$response = $this->router->route('tests/TestController/withQuery?key=value');

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertNotSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertSame([
			'params' => [
				'key' => 'value',
			],
		], $response->data);

		// String body
		$response = $this->router->route('tests/TestController/withQuery?key=value', 'body');

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertNotSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertSame([
			'params' => [
				'key' => 'value',
				'body' => 'body',
			],
		], $response->data);
	}

	/**
	 * @covers OObject::route
	 */
	public function testRemoteCall()
	{
		$response = $this->router->route('https://www.google.com', [
			'http_headers' => [
				'Accept' => '*/*'
			],
			'http_content_type' => 'text',
			'http_accept' => '*/*',
			'http_username' => 'user',
			'http_password' => 'password',
			'http_raw' => true,
			'http_debug' => true,
			'http_user_agent' => 'Test HTTP Client',
			'http_method' => 'post',
		]);

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertIsString($response->data);
	}
}
