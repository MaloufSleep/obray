<?php

/********************************************************************************************************************
 * OUsers:    User/Permission Manager
 ********************************************************************************************************************/
class oRolePermissions extends ODBO
{
	public function __construct()
	{
		parent::__construct();

		$this->table = 'oRolePermissions';
		$this->table_definition = array(
			'orole_permission_id' => array('primary_key' => TRUE),
			'orole_id' => array('data_type' => 'integer', 'required' => TRUE),
			'opermission_id' => array('data_type' => 'integer', 'required' => TRUE)
		);

		$this->permissions = array();
	}
}
