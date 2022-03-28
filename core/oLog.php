<?php

use Illuminate\Contracts\Debug\ExceptionHandler;

class oLog extends OObject
{
	/**
	 * @var \Illuminate\Contracts\Debug\ExceptionHandler
	 */
	protected static $exceptionHandler;

	/**
	 * @var \Monolog\Logger
	 */
	protected static $logger;

	public function __construct()
	{
		$this->permissions = array(
			'object' => 1,
			'logError' => 1,
			'logInfo' => 1,
			'logDebug' => 1
		);
	}

	public static function setExceptionHandler(?ExceptionHandler $exceptionHandler)
	{
		self::$exceptionHandler = $exceptionHandler;
	}

	public static function setMonologLogger(?\Monolog\Logger $logger)
	{
		self::$logger = $logger;
	}

	public function logError($oProjectEnum, Exception $exception, $customMessage = "")
	{
		if (isset(self::$exceptionHandler)) {
			if (self::$exceptionHandler->shouldReport($exception)) {
				self::$exceptionHandler->report($exception);
			}

			return;
		}

		if (isset(self::$logger)) {
			self::$logger->log(\Monolog\Logger::ERROR, $exception->getMessage(), compact('oProjectEnum', 'customMessage'));
			return;
		}

		$message = $exception->getMessage() . PHP_EOL;
		if ($customMessage != "") {
			$message .= "Custom Message: " . $customMessage . PHP_EOL;
		}
		$message .= $this->getStackTrace($exception);
		$filepath = $this->getFilePath($oProjectEnum, oLogTypeEnum::ERROR);
		$message = date('Y-m-d h:i:s', time()) . ' ' . $message . PHP_EOL;
		$this->writeLog($filepath, $message);
	}

	public function logInfo($oProjectEnum, $message)
	{
		if (isset(self::$logger)) {
			self::$logger->log(\Monolog\Logger::INFO, $message, compact('oProjectEnum'));
			return;
		}

		$message = date('Y-m-d h:i:s', time()) . ' ' . $message . PHP_EOL;
		$filepath = $this->getFilePath($oProjectEnum, oLogTypeEnum::INFO);
		$this->writeLog($filepath, $message);
	}

	public function logDebug($oProjectEnum, $message)
	{
		if (isset(self::$logger)) {
			self::$logger->log(\Monolog\Logger::DEBUG, $message, compact('oProjectEnum'));
			return;
		}

		$message = date('Y-m-d h:i:s', time()) . ' ' . $message . PHP_EOL;
		$filepath = $this->getFilePath($oProjectEnum, oLogTypeEnum::DEBUG);
		$this->writeLog($filepath, $message);
	}

	private function getFilePath($oProjectEnum, $oLogTypeEnum)
	{
		$filepath = __LOGS__ . __APP__ . '/' . $oProjectEnum . '/' . $oLogTypeEnum . '/' . Date('Y-m-d') . '_' . $oProjectEnum . '_' . $oLogTypeEnum . '.log';
		return $filepath;
	}

	private function writeLog($filepath, $message)
	{
		if (!file_exists(dirname($filepath))) {
			$old_umask = umask(0);
			mkdir(dirname($filepath), 0777, true);
			umask($old_umask);
		}
		$createFile = !file_exists($filepath);

		file_put_contents($filepath, $message . PHP_EOL, FILE_APPEND | LOCK_EX);

		if ($createFile) {
			chmod($filepath, 0666);
		}
	}
}
