<?php

namespace tests\Object;

use App\models\oTestModel;
use cLegacyController;
use oLegacyModel;
use OObject;
use tests\TestCase;

/**
 * @covers OObject
 */
class RouteTest extends TestCase
{
	public function testSuccessfulRoute()
	{
		$response = $this->router->route('app/TestController/test');

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertNotSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertSame([
			'Success message',
		], $response->data);
	}

	public function testSuccessfulRouteNotDirect()
	{
		$response = $this->router->route('app/TestController/test', [], false);

		$this->assertNotError();
		$this->assertNotError($response);
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertNotSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertSame([
			'Success message',
		], $response->data);
	}

	public function testRouteWithQuery()
	{
		// Array body
		$response = $this->router->route('app/TestController/withQuery?key=value');

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
		$response = $this->router->route('app/TestController/withQuery?key=value', 'body');

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

	public function testRemoteCallPost()
	{
		$response = $this->router->route('https://jsonplaceholder.typicode.com/todos', [
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
		$this->assertIsObject($response->data);
	}

	public function testRemoteCallPatch()
	{
		$response = $this->router->route('https://jsonplaceholder.typicode.com/todos/1', [
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
			'http_method' => 'patch',
            'body' => ''
		]);

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertIsObject($response->data);
	}

	public function testRemoteCallPut()
	{
		$response = $this->router->route('https://jsonplaceholder.typicode.com/todos/1?key=value', [
			'http_headers' => [
				'Accept' => '*/*'
			],
			'http_content_type' => 'text',
			'http_accept' => '*/*',
			'http_username' => 'user',
			'http_raw' => true,
			'http_debug' => true,
			'http_user_agent' => 'Test HTTP Client',
			'http_method' => 'put',
		]);

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertIsObject($response->data);
	}

	public function testRemoteCallBodyOnly()
	{
		$response = $this->router->route('https://jsonplaceholder.typicode.com/todos', [
			'body' => [
				'title' => 'Test Title',
			],
		]);

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertIsObject($response->data);
	}

	public function testRemoteCallJsonBody()
	{
		$response = $this->router->route('https://jsonplaceholder.typicode.com/todos', [
			'title' => 'Test Title',
			'completed' => true,
			'http_content_type' => 'application/json',
		]);

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertIsObject($response->data);
	}

	public function testRemoteCallNoHeaders()
	{
		$response = $this->router->route('https://jsonplaceholder.typicode.com/todos/1');

		$this->assertNotError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertIsObject($response->data);
	}

	public function testRemoteCallNotSuccess()
	{
		$response = $this->router->route('https://jsonplaceholder.typicode.com/todos/-1');

		$this->assertError();
		$this->assertInstanceOf(OObject::class, $response);
		$this->assertSame($this->router, $response);
		$this->assertNotNull($response->data);
		$this->assertIsString($response->data);
		$this->assertSame('{}', $response->data);
	}

	public function testNamespacedModelCreateObject()
	{
		$object = $this->router->route('app/oTestModel');

		$this->assertNotError();
		$this->assertNotSame($this->router, $object);
		$this->assertInstanceOf(oTestModel::class, $object);
	}

	public function testLegacyModelCreateObject()
	{
		$object = $this->router->route('m/oLegacyModel');

		$this->assertNotError();
		$this->assertNotSame($this->router, $object);
		$this->assertInstanceOf(oLegacyModel::class, $object);
	}

	public function testLegacyControllerCreateObject()
	{
		$object = $this->router->route('LegacyController');

		$this->assertNotError();
		$this->assertNotSame($this->router, $object);
		$this->assertInstanceOf(cLegacyController::class, $object);
	}
}
