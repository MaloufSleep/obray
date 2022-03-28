<?php

namespace tests\Log;

use Exception;
use oLog;
use tests\TestCase;

/**
 * @covers \oLog
 */
class DiskLoggerTest extends TestCase
{
	protected oLog $logger;

	public const PROJECT = 'test';

	protected function setUp(): void
	{
		parent::setUp();

		$this->logger = new oLog();

		shell_exec('rm -rf ' . __LOGS__ . __APP__);
	}

	public function testLogError()
	{
		$this->logger->logError(static::PROJECT, new Exception('yodles'), 'howdy');

		$this->assertFileExists($logFile = __LOGS__ . __APP__ . '/' . static::PROJECT . '/Error/' . Date('Y-m-d') . '_' . static::PROJECT . '_Error.log');
		$contents = file_get_contents($logFile);
		$this->assertStringContainsString('yodles' . PHP_EOL . 'Custom Message: howdy' . PHP_EOL, $contents);
	}

	public function testLogInfo()
	{
		$this->logger->logInfo(static::PROJECT, 'yodles');

		$this->assertFileExists($logFile = __LOGS__ . __APP__ . '/' . static::PROJECT . '/Info/' . Date('Y-m-d') . '_' . static::PROJECT . '_Info.log');
		$contents = file_get_contents($logFile);
		$this->assertStringContainsString(' yodles' . PHP_EOL, $contents);
	}

	public function testLogDebug()
	{
		$this->logger->logDebug(static::PROJECT, 'yodles');

		$this->assertFileExists($logFile = __LOGS__ . __APP__ . '/' . static::PROJECT . '/Debug/' . Date('Y-m-d') . '_' . static::PROJECT . '_Debug.log');
		$contents = file_get_contents($logFile);
		$this->assertStringContainsString(' yodles' . PHP_EOL, $contents);
	}
}
