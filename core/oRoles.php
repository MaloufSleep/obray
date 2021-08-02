<?php
/********************************************************************************************************************
 *
 * OUsers:    User/Permission Manager
 ********************************************************************************************************************/
class oRoles extends ODBO
{
	public function __construct()
	{
		parent::__construct();

		$this->table = 'oRoles';
		$this->table_definition = array(
			'orole_id' => array('primary_key' => TRUE),
			'orole_code' => array('data_type' => 'varchar(25)', 'required' => TRUE),
			'orole_description' => array('data_type' => 'varchar(128)', 'required' => FALSE)
		);

		$this->permissions = array();
	}
}
