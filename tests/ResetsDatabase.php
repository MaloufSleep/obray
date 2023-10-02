<?php

namespace tests;

use PDO;

trait ResetsDatabase
{
    protected PDO $pdo;

    protected function initializeResetsDatabase(): void
    {
        $this->pdo = new PDO(
            'mysql:host=' . __OBRAY_DATABASE_HOST__ . ';charset=utf8',
            __OBRAY_DATABASE_USERNAME__,
            __OBRAY_DATABASE_PASSWORD__,
            [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        date_default_timezone_set('UTC');

        $this->pdo->exec('DROP DATABASE IF EXISTS `' . __OBRAY_DATABASE_NAME__ . '`');
        $this->pdo->exec('CREATE DATABASE `' . __OBRAY_DATABASE_NAME__ . '`');
        $this->pdo->exec('USE ' . __OBRAY_DATABASE_NAME__);
        $this->pdo->exec('
			create table `test_table` (
			    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			    `column_int` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			    `column_string` varchar(191) NULL,
			    `OCDT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
			    `OMDT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
			    `OCU` BIGINT UNSIGNED,
			    `OMU` BIGINT UNSIGNED
			) default character set utf8mb4 collate \'utf8mb4_general_ci\'
		');
    }
}
