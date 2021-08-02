<?php

/********************************************************************************************************************
 * OUsers:    User/Permission Manager
 ********************************************************************************************************************/
class oUserRoles extends ODBO
{
	public function __construct()
	{
		parent::__construct();

		$this->table = 'oUserRoles';
		$this->table_definition = array(
			'ouser_role_id' => array('primary_key' => TRUE),
			'orole_id' => array('data_type' => 'integer', 'required' => TRUE),
			'ouser_id' => array('data_type' => 'integer', 'required' => TRUE)
		);

		$this->permissions = array(
			'object' => 'any',
			'getArray' => 'any'
		);
	}

	public function getArray()
	{
		$sql = "SELECT oPermissions.opermission_code, oRoles.orole_code 
                        FROM oUserRoles
                        JOIN oRoles ON oRoles.orole_id = oUserRoles.orole_id
                LEFT JOIN oRolePermissions ON oRolePermissions.orole_id = oUserRoles.orole_id
                        JOIN oPermissions ON oPermissions.opermission_id = oRolePermissions.opermission_id
                    WHERE oUserRoles.ouser_id = :ouser_id";

		try {
			$statement = $this->dbh->prepare($sql);
			$statement->bindValue(':ouser_id', $_SESSION["ouser"]->ouser_id);
			$result = $statement->execute();
			$this->data = [];
			$statement->setFetchMode(PDO::FETCH_OBJ);
			while ($row = $statement->fetch()) {
				$this->data[] = $row;
			}
		} catch (Exception $e) {
			$this->throwError($e);
			$this->logError(oCoreProjectEnum::ODBO, $e);
		}

		$roles = array();
		$permissions = array();
		foreach ($this->data as $codes) {
			if (!in_array($codes->orole_code, $roles)) {
				$roles[] = $codes->orole_code;
			}
			if (!in_array($codes->opermission_code, $permissions)) {
				$permissions[] = $codes->opermission_code;
			}
		}

		$this->data = array(
			"permissions" => $permissions,
			"roles" => $roles
		);
	}
}
