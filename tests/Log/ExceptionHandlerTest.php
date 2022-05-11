<?php

namespace tests\Log;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use oLog;
use tests\TestCase;

/**
 * @covers \oLog
 */
class ExceptionHandlerTest extends TestCase
{
	protected oLog $logger;

	protected ExceptionHandler $handler;

	public const PROJECT = 'test';

	protected function setUp(): void
	{
		parent::setUp();

		$this->logger = new oLog();
		$this->handler = new \tests\Log\ExceptionHandler();

		shell_exec('rm -rf ' . __LOGS__ . __APP__);

		oLog::setExceptionHandler($this->handler);
	}

	protected function teardown(): void
	{
		oLog::setExceptionHandler(null);

		parent::tearDown();
	}

	public function testLogError()
	{
		$this->logger->logError(static::PROJECT, $exception = new Exception('yodles'), 'howdy');

		$this->assertFileDoesNotExist(__LOGS__ . __APP__ . '/' . static::PROJECT . '/Error/' . Date('Y-m-d') . '_' . static::PROJECT . '_Error.log');
		$this->assertNotEmpty($reported = $this->handler->getReported());
		$this->assertIsArray($reported);
		$this->assertCount(1, $reported);
		$this->assertArrayhasKey(0, $reported);
		$this->assertSame($exception, $reported[0]);

		$this->assertSame($exception, $this->logger->getLastException());
	}
}
