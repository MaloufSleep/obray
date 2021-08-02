<?php

namespace tests;

use OObject;
use Symfony\Component\Console\Output\NullOutput;

class TestCase extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var \OObject
	 */
	protected $router;

	protected function setUp(): void
	{
		parent::setUp();

		$this->router = new OObject();
		$this->router->setOutput(new NullOutput());

		define('__OBRAY_ROUTES__', serialize([
			'obray' => realpath(__DIR__ . '/../core') . '/',
			'tests' => __DIR__ . '/controllers',
		]));
		define('__OBRAY_SITE_ROOT__', realpath(__DIR__ . '/../') . '/');
		define('__OBRAY_NAMESPACE_ROOT__', realpath(__DIR__ . '/..') . '/');
		define('__OBRAY_APP_NAME__', 'Tests');
	}

	protected function assertNotError()
	{
		$errors = json_encode($this->router->errors ?? []);

		$this->assertFalse($this->router->isError(), $errors);
	}
}
