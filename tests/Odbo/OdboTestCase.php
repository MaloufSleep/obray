<?php

namespace tests\Odbo;

use ODBO;
use PDO;
use tests\Models\TestModel;
use tests\TestCase;

class OdboTestCase extends TestCase
{
	/**
	 * @var \ODBO
	 */
	protected $testModel;

	/**
	 * @var \PDO
	 */
	protected $pdo;

	protected function setUp(): void
	{
		parent::setUp();

		$this->pdo = new PDO(
			'mysql:host=' . __OBRAY_DATABASE_HOST__ . ';charset=utf8',
			__OBRAY_DATABASE_USERNAME__,
			__OBRAY_DATABASE_PASSWORD__,
			[
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			]
		);

		$timeZone = $this->pdo->query('SELECT @@session.time_zone')->fetchColumn();
		date_default_timezone_set($timeZone);

		$this->pdo->exec('DROP DATABASE IF EXISTS `' . __OBRAY_DATABASE_NAME__ . '`');
		$this->pdo->exec('CREATE DATABASE `' . __OBRAY_DATABASE_NAME__ . '`');
		$this->pdo->exec('USE ' . __OBRAY_DATABASE_NAME__);
		$this->pdo->exec('
			create table `test_table` (
			    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			    `column_int` BIGINT UNSIGNED NULL,
			    `OCDT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
			    `OMDT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
			    `OCU` BIGINT UNSIGNED NOT NULL,
			    `OMU` BIGINT UNSIGNED NOT NULL
			) default character set utf8mb4 collate \'utf8mb4_general_ci\'
		');

		$this->testModel = new TestModel();
		$this->testModel->dbh = getDatabaseConnection(true);
	}
}
