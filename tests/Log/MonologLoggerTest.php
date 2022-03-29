<?php

namespace tests\Log;

use Exception;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use oLog;
use tests\TestCase;

/**
 * @covers \oLog
 */
class MonologLoggerTest extends TestCase
{
	protected oLog $logger;

	protected Logger $monoLogger;

	protected TestHandler $handler;

	public const PROJECT = 'test';

	protected function setUp(): void
	{
		parent::setUp();

		$this->logger = new oLog();
		$this->monoLogger = new Logger('Test');
		$this->monoLogger->pushHandler($this->handler = new TestHandler());

		shell_exec('rm -rf ' . __LOGS__ . __APP__);

		oLog::setMonologLogger($this->monoLogger);
	}

	protected function tearDown(): void
	{
		oLog::setMonologLogger(null);

		parent::tearDown();
	}

	public function testLogError()
	{
		$this->logger->logError(static::PROJECT, new Exception('yodles'), 'howdy');

		$this->assertFileDoesNotExist(__LOGS__ . __APP__ . '/' . static::PROJECT . '/Error/' . Date('Y-m-d') . '_' . static::PROJECT . '_Error.log');
		$this->assertTrue($this->handler->hasErrorRecords());
		$this->assertTrue($this->handler->hasErrorThatContains('yodles'));
	}

	public function testLogInfo()
	{
		$this->logger->logInfo(static::PROJECT, 'yodles');

		$this->assertFileDoesNotExist(__LOGS__ . __APP__ . '/' . static::PROJECT . '/Info/' . Date('Y-m-d') . '_' . static::PROJECT . '_Info.log');
		$this->assertTrue($this->handler->hasInfoRecords());
		$this->assertTrue($this->handler->hasInfoThatContains('yodles'));
	}

	public function testLogDebug()
	{
		$this->logger->logDebug(static::PROJECT, 'yodles');

		$this->assertFileDoesNotExist(__LOGS__ . __APP__ . '/' . static::PROJECT . '/Debug/' . Date('Y-m-d') . '_' . static::PROJECT . '_Debug.log');
		$this->assertTrue($this->handler->hasDebugRecords());
		$this->assertTrue($this->handler->hasDebugThatContains('yodles'));
	}
}
