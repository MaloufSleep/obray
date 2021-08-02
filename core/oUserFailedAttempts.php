<?php

/********************************************************************************************************************
 * OUsers:    User/Permission Manager
 ********************************************************************************************************************/
class oUserFailedAttempts extends ODBO
{
	public function __construct()
	{
		parent::__construct();

		$this->table = 'oUserFailedAttempts';
		$this->table_definition = array(
			'ouser_attempt_id' => array('primary_key' => TRUE),
			'ouser_email' => array('data_type' => 'varchar(50)'),
			'ouser_password' => array('data_type' => 'varchar(50)'),
			'ouser_attempt_ip' => array('data_type' => 'varchar(39)'),
			'ouser_attempt_agent' => array('data_type' => 'varchar(128)')
		);

		$this->permissions = array(
			'object' => 1,
			'add' => 1,
			'get' => 1,
			'update' => 1,
			'count' => 1
		);
	}
}
