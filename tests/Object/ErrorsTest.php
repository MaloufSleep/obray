<?php

namespace tests\Object;

use tests\TestCase;

class ErrorsTest extends TestCase
{
    public function testErrorsActuallyBeingDefinedStillIsNotSet()
    {
        $this->assertFalse(isset($this->router->errors));
    }

	/**
	 * @covers OObject
	 */
	public function testErrorCanBeThrown()
	{
		$this->assertFalse($this->router->isError());
		$this->router->throwError('Test Error');
		$this->assertTrue($this->router->isError());

		$this->assertIsArray($this->router->errors);
		$this->assertArrayHasKey('general', $this->router->errors);
		$this->assertIsArray($this->router->errors['general']);

		foreach ($this->router->errors['general'] as $error) {
			$this->assertIsString($error);
			$this->assertSame('Test Error', $error);
		}
	}
}
