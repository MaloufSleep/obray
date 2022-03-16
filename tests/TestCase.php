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
		$this->router::setContainerSingleton(null);

		define('__OBRAY_ROUTES__', serialize([
			'obray' => realpath(__DIR__ . '/../core') . '/',
			'app' => realpath(__DIR__ . '/../test_files') . '/',
			'm' => realpath(__DIR__ . '/../test_files/models') . '/',
		]));
		define('__OBRAY_SITE_ROOT__', realpath(__DIR__ . '/../test_files') . '/');
		define('__OBRAY_NAMESPACE_ROOT__', realpath(__DIR__ . '/../test_files') . '/');
		define('__OBRAY_APP_NAME__', 'App');
		define('__APP__', 'App');
		define('__OBRAY_DATABASE_HOST__', 'mysql');
		define('__OBRAY_DATABASE_NAME__', 'obray');
		define('__OBRAY_DATABASE_USERNAME__', 'obray');
		define('__OBRAY_DATABASE_PASSWORD__', 'obray');

		define('__LOGS__', realpath(__DIR__ . '/../logs') . '/');
	}

	protected function assertNotError(?OObject $object = null)
	{
		$object = $object ?? $this->router;

		$errors = json_encode($object->errors ?? []);

		$this->assertFalse($object->isError(), $errors);
	}

	protected function assertError(?OObject $object = null)
	{
		$object = $object ?? $this->router;

		$errors = json_encode($object->errors ?? []);

		$this->assertTrue($object->isError(), $errors);
	}
}
