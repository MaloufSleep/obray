<?php

namespace tests\Object;

use Exception;
use tests\TestCase;

/**
 * @covers OObject
 */
class GetStackTraceTest extends TestCase
{
	public function testGetStackTraceBasic()
	{
		$this->assertIsString($this->router->getStackTrace(new Exception()));
	}

	public function provideExceptionArguments(): array
	{
		return [
			['Hello!'],
			[null],
			[fopen('php://memory', 'w')],
			[1],
		];
	}

	/**
	 * @param $arg
	 * @dataProvider provideExceptionArguments
	 */
	public function testGetStackTraceWithArgument($arg)
	{
		try {
			throwExceptionWithArgument($arg);
		} catch (Exception $e) {
			$this->assertIsString($this->router->getStackTrace($e));
		}
	}
}

/**
 * @param mixed $arg
 * @throws \Exception
 */
function throwExceptionWithArgument($arg)
{
	throw new Exception;
}