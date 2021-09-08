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

		$_SESSION['ouser'] = new class {
			public $ouser_permission_level = 1;
		};

		$this->router = new OObject();
		$this->router->setOutput(new NullOutput());

		define('__OBRAY_ROUTES__', serialize([
			'obray' => realpath(__DIR__ . '/../core') . '/',
			'tests' => __DIR__,
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

	protected function assertError()
	{
		$errors = json_encode($this->router->errors ?? []);

		$this->assertTrue($this->router->isError(), $errors);
	}
}
