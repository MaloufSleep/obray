<?php

namespace Tests;

use OObject;

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
	}
}
