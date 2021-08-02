<?php

/********************************************************************************************************************
 * OUsers:    User/Permission Manager
 ********************************************************************************************************************/

class oUserPermissions extends ODBO
{
	public function __construct()
	{
		parent::__construct();

		$this->table = 'oUserPermissions';
		$this->table_definition = array(
			'ouser_permission_id' => array('primary_key' => TRUE),
			'opermission_id' => array('data_type' => 'integer', 'required' => TRUE),
			'ouser_id' => array('data_type' => 'integer', 'required' => TRUE)
		);

		$this->permissions = array(
			'object' => 'any',
			'getArray' => 'any'
		);
	}

	public function getArray()
	{
		$this->get(array(
			"ouser_id" => $_SESSION["ouser"]->ouser_id
		));

		if (empty($this->data)) {
			return array();
		}

		$data = array();
		foreach ($this->data as $dati) {
			$data[] = $dati->opermission_id;
		}

		$oPermissions = $this->route("/obray/oPermissions/get/", array(
			"opermission_id" => implode("|", $data)
		))->data;

		if (empty($oPermissions)) {
			return array();
		}

		$data = array();
		foreach ($oPermissions as $oPermission) {
			$data[] = $oPermission->opermission_code;
		}

		$this->data = $data;
		return $data;
	}
}
