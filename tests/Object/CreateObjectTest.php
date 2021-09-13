<?php

namespace tests\Object;

use App\models\oTestModel;
use Exception;
use OObject;
use App\controllers\cTestController;
use App\controllers\Nested\cNestedController;
use tests\TestCase;
use tests\TestContainer;

/**
 * @covers OObject
 */
class CreateObjectTest extends TestCase
{
	public function test404()
	{
		$response = $this->router->route('app/DoesNotExist');

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
		$response = $this->router->route('app/TestController');

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
		$response = $this->router->route('app/Nested/NestedController');

		$this->assertInstanceOf(cNestedController::class, $response);
		$this->assertObjectNotHasAttribute('data', $response);
		$this->assertFalse($response->isError());
		$this->assertObjectNotHasAttribute('errors', $response);
	}

	public function testCreatingViaContainer()
	{
		$this->router::setContainerSingleton(new TestContainer());

		$object = $this->router->route('app/oTestModel');

		$this->assertNotError();
		$this->assertNotSame($this->router, $object);
		$this->assertInstanceOf(oTestModel::class, $object);
	}

	public function testExceptionCreating()
	{
		$container = new TestContainer();
		$container->bind(oTestModel::class, function () {
			throw new Exception();
		});

		$this->router::setContainerSingleton($container);

		$object = $this->router->route('app/oTestModel');

		$this->assertError();
		$this->assertSame($this->router, $object);
		$this->assertNotNull($object->errors);
		$this->assertJsonStringEqualsJsonString(json_encode([
			'general' => [''],
			'notfound' => [
				'Route not found object: app/oTestModel',
			]
		]), json_encode($object->errors));
	}
}
