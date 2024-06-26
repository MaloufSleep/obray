<?php

namespace tests\Models;

use ODBO;

class TestModel extends ODBO
{
	public function __construct()
	{
		parent::__construct();

		$this->table = "test_table";
		$this->table_definition = [
			'id' => ['primary_key' => TRUE],
			'column_int' => [
				'data_type' => 'integer',
				'required' => true,
				'options' => [
					'String at index 0'
				],
			],
			'column_string' => [
				'data_type' => 'varchar',
			],
		];
	}
}
