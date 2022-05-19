<?php

namespace tests;

use OObject;
use OUsers;
use Symfony\Component\Console\Output\NullOutput;

class TestCase extends \PHPUnit\Framework\TestCase
{
	use FakesAuthentication;

	/**
	 * @var \OObject
	 */
	protected $router;

	protected function setUp(): void
	{
		parent::setUp();

		global $_SESSION;
		$_SESSION = [];
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);

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
		define('__APP__', 'Obray');
		define('__OBRAY_DATABASE_HOST__', 'mysql');
		define('__OBRAY_DATABASE_NAME__', 'obray');
		define('__OBRAY_DATABASE_USERNAME__', 'obray');
		define('__OBRAY_DATABASE_PASSWORD__', 'obray');

		define('__LOGS__', realpath(__DIR__ . '/../logs') . '/');
	}

	protected function tearDown(): void
	{
		if (class_exists(OUsers::class)) {
			OUsers::$shouldAuthenticate = true;
		}

		parent::tearDown();
	}


	protected function assertNotError(?OObject $object = null)
	{
		$object = $object ?? $this->router;

		$errors = json_encode($object->errors ?? []);

		$this->assertFalse($object->isError(), $errors);
	}

	protected function assertError(?OObject $object = null, array $errors = [])
	{
		$object = $object ?? $this->router;

		$this->assertTrue($object->isError(), empty($object->errors) ? 'No error reported' : json_encode($object->errors));

		if (!empty($errors)) {
			$this->assertEqualsCanonicalizing($errors, $object->errors ?? []);
		}
	}

	protected function route(string $path, array $params = [], bool $direct = false)
	{
		return $this->router->route($path, $params, $direct);
	}
}
