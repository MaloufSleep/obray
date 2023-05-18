<?php

namespace tests\Odbo;

use ODBO;
use PDO;
use tests\Models\TestModel;
use tests\ResetsDatabase;
use tests\TestCase;

class OdboTestCase extends TestCase
{
    use ResetsDatabase;

	/**
	 * @var \ODBO
	 */
	protected $testModel;

	protected function setUp(): void
	{
		parent::setUp();
        $this->initializeResetsDatabase();

		$this->testModel = new TestModel();
		$this->testModel->dbh = getDatabaseConnection(true);
	}
}
