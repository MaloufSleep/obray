<?php

/********************************************************************************************************************
 * OUsers:    User/Permission Manager
 ********************************************************************************************************************/
class oPermissions extends ODBO
{
	public function __construct()
	{
		parent::__construct();

		$this->table = 'oPermissions';
		$this->table_definition = array(
			'opermission_id' => array('primary_key' => TRUE),
			'opermission_code' => array('data_type' => 'varchar(25)', 'required' => TRUE),
			'opermission_description' => array('data_type' => 'varchar(128)', 'required' => FALSE)
		);

		$this->permissions = array();
	}
}
