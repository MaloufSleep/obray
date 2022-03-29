<?php

namespace tests\Log;

use Throwable;

class ExceptionHandler implements \Illuminate\Contracts\Debug\ExceptionHandler
{
	/**
	 * @var array<int, \Throwable>
	 */
	protected array $reported;

	public function report(Throwable $e)
	{
		$this->reported[] = $e;
	}

	public function shouldReport(Throwable $e)
	{
		return true;
	}

	public function render($request, Throwable $e)
	{
		// noop
	}

	public function renderForConsole($output, Throwable $e)
	{
		// noop
	}

	public function getReported()
	{
		return $this->reported;
	}
}
