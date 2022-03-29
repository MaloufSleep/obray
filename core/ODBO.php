<?php

/********************************************************************************************************************
 * ODBO:    This is the database interface object built specifically for MySQL and MariaDB.
 ********************************************************************************************************************/
class ODBO extends OObject
{
	/**
	 * @var \PDO
	 */
	public $dbh;
	public $enable_system_columns = TRUE;

	private static $shouldUseReader = true;
	/**
	 * @var array
	 */
	public $data_types;
	/**
	 * @var bool
	 */
	public $enable_column_additions;
	/**
	 * @var bool
	 */
	public $enable_column_removal;
	/**
	 * @var bool
	 */
	public $enable_data_type_changes;
	/**
	 * @var string
	 */
	public $table;
	/**
	 * @var array
	 */
	public $table_definition;
	/**
	 * @var string
	 */
	public $primary_key_column;
	/**
	 * @var bool
	 */
	public $is_transaction;
	/**
	 * @var array|false
	 */
	public $data;
	public $reader;
	/**
	 * @var string
	 */
	public $sql;
	/**
	 * @var bool
	 */
	public $script;
	/**
	 * @var array
	 */
	public $required;
	/**
	 * @var int|mixed|string
	 */
	public $parent_column;
	/**
	 * @var int|string
	 */
	public $slug_key_column;
	/**
	 * @var int|string
	 */
	public $slug_value_column;
	/**
	 * @var string
	 */
	public $where;
	/**
	 * @var bool
	 */
	public $filter;
	/**
	 * @var int
	 */
	public $recordcount;
	/**
	 * @var array|mixed
	 */
	public $params;
	public $column;
	/**
	 * @var mixed|string
	 */
	public $order;
	/**
	 * @var array
	 */
	public $with;

	public function __construct()
	{
		$this->primary_key_column = '';
		$this->data_types = array();

		$this->enable_column_additions = TRUE;
		$this->enable_column_removal = TRUE;
		$this->enable_data_type_changes = TRUE;
		$this->enable_system_columns = TRUE;

		if (!isset($this->table)) {
			$this->table = '';
		}
		if (!isset($this->table_definition)) {
			$this->table_definition = array();
		}

		if (!defined('__OBRAY_DATATYPES__')) {

			define('__OBRAY_DATATYPES__', serialize(array(
				'varchar' => array('sql' => ' VARCHAR(size) COLLATE utf8_general_ci ', 'my_sql_type' => 'varchar(size)', 'validation_regex' => ''),
				'mediumtext' => array('sql' => ' MEDIUMTEXT COLLATE utf8_general_ci ', 'my_sql_type' => 'mediumtext', 'validation_regex' => ''),
				'text' => array('sql' => ' TEXT COLLATE utf8_general_ci ', 'my_sql_type' => 'text', 'validation_regex' => ''),
				'integer' => array('sql' => ' int ', 'my_sql_type' => 'int(11)', 'validation_regex' => '/^([+,-]?[0-9])*$/'),
				'uninteger' => array('sql' => ' int(11) unsigned NOT NULL DEFAULT \'0\'  ', 'my_sql_type' => 'int(11) unsigned', 'validation_regex' => '/^([+,-]?[0-9])*$/'),
				'float' => array('sql' => ' float ', 'my_sql_type' => 'float', 'validation_regex' => '/[0-9\.]*/'),
				'boolean' => array('sql' => ' tinyint(1) ', 'my_sql_type' => 'tinyint(1)', 'validation_regex' => ''),
				'datetime' => array('sql' => ' datetime ', 'my_sql_type' => 'datetime', 'validation_regex' => ''),
				'password' => array('sql' => ' varchar(255) ', 'my_sql_type' => 'varchar(255)', 'validation_regex' => '')
			)));
		}
	}
	/**
	 * @param bool $shouldUseReader
	 */
	public static function setUseReader($shouldUseReader)
	{
		self::$shouldUseReader = $shouldUseReader;
	}

	/**
	 * @return bool
	 */
	public static function getShouldUseReader()
	{
		return self::$shouldUseReader;
	}

	public function startTransaction()
	{
		$this->dbh->beginTransaction();
		$this->is_transaction = TRUE;
	}

	public function commitTransaction()
	{
		if (!$this->is_transaction) {
			return false;  //This likely means that the transaction was rolled back and should therefore not be committed. (that or there was never a transaction to begin with).
		}
		$success = $this->dbh->commit();
		$this->is_transaction = FALSE;
		return $success;
	}

	public function rollbackTransaction()
	{
		$success = $this->dbh->rollBack();
		$this->is_transaction = FALSE;
		return $success;
	}

	public function getOptions($params = array())
	{
		$this->data = FALSE;
		if (!empty($this->table_definition[$params["column"]]["options"])) {
			if (isset($params['key']) && strlen(trim($params['key']))) {
				if (!empty($this->table_definition[$params["column"]]["options"][$params["key"]])) {
					$this->data = $this->table_definition[$params["column"]]["options"][$params["key"]];
				} else {
					$this->data = FALSE;
				}
			} else if (isset($params['value']) && strlen(trim($params['value']))) {
				$key = array_search($params["value"], $this->table_definition[$params["column"]]["options"]);
				if ($key !== FALSE) {
					$this->data = $key;
				} else {
					$this->data = FALSE;
				}
			} else {
				$this->data = $this->table_definition[$params["column"]]["options"];
			}
		}
	}

	public function setDatabaseConnection(PDO $dbh)
	{
		$this->dbh = $dbh;
	}

	public function setReaderDatabaseConnection(PDO $reader)
	{
		$this->reader = $reader;
	}

	/********************************************************************
	 * GETTABLEDEFINITION
	 ********************************************************************/

	public function getTableDefinition()
	{
		$this->data = $this->table_definition;
	}

	private function getWorkingDef()
	{
		$this->required = array();
		foreach ($this->table_definition as $key => $def) {
			if (isset($def['required']) && $def['required'] == TRUE) {
				$this->required[$key] = TRUE;
			}
			if (isset($def['primary_key'])) {
				$this->primary_key_column = $key;
			}
			if (isset($def['parent']) && $def['parent'] == TRUE) {
				$this->parent_column = $key;
			}
			if (isset($def['slug_key']) && $def['slug_key'] == TRUE) {
				$this->slug_key_column = $key;
			}
			if (isset($def['slug_value']) && $def['slug_value'] == TRUE) {
				$this->slug_value_column = $key;
			}
		}
	}

	/********************************************************************
	 *
	 * ADD function
	 *******************************************************************
	 * @throws \Exception
	 */

	public function add($params = array())
	{
		if (empty($this->dbh)) {
			return $this;
		}

		$sql = '';
		$sql_values = '';
		$data = array();
		$this->data_types = unserialize(__OBRAY_DATATYPES__);

		$this->getWorkingDef();

		if (isset($this->slug_key_column) && isset($this->slug_value_column) && isset($params[$this->slug_key_column])) {
			if (isset($this->parent_column) && isset($params[$this->parent_column])) {
				$parent = $params[$this->parent_column];
			} else {
				$parent = null;
			}
			$params[$this->slug_value_column] = $this->getSlug($params[$this->slug_key_column], $this->slug_value_column, $parent);
		}

		foreach ($params as $key => $param) {

			if (isset($this->table_definition[$key])) {

				$def = $this->table_definition[$key];
				if (!empty($def["options"])) {
					$options = array_change_key_case($def["options"], CASE_LOWER);
					if (!empty($options[strtolower($param)]) && !is_array($options[strtolower($param)])) {
						$data[$key] = $options[strtolower($param)];
						$option_is_set = TRUE;
					} else {
						$data[$key] = $param;
					}
				} else {
					$data[$key] = $param;
				}
				$data_type = $this->getDataType($def);

				if (isset($this->required[$key])) {
					unset($this->required[$key]);
				}
				if (isset($def['data_type']) && !empty($this->data_types[$data_type['data_type']]['validation_regex']) && !preg_match($this->data_types[$data_type['data_type']]['validation_regex'], $param) && $param == NULL) {
					$this->throwError(($def['error_message'] ?? isset($def['label'])) ? $def['label'] . ' is invalid.' : $key . ' is invalid.', '500', $key);
				}

				if (isset($def['data_type']) && $def['data_type'] == 'password') {
					$salt = '$2a$12$' . $this->generateToken();
					$data[$key] = crypt($param, $salt);
				}

				if (isset($param)) {
					if (!empty($sql)) {
						$sql .= ',';
						$sql_values .= ',';
					}
					$sql .= $key;
					$sql_values .= ':' . $key;
				}
			}
		}

		if (!empty($this->required)) {
			foreach ($this->required as $key => $value) {
				$this->throwError($key . ' is required.', '500', $key);
			}
		}

		if ($this->isError()) {
			$this->throwError($this->general_error ?? 'There was an error on this form, please make sure the below fields were completed correctly: ');
			return $this;
		}

		if ($this->enable_system_columns) {
			$ocu = $_SESSION['ouser']->ouser_id ?? 0;
			$system_columns = ", OCDT, OCU ";
			$system_values = ', \'' . date('Y-m-d H:i:s') . '\', ' . $ocu;
		} else {
			$system_columns = "";
			$system_values = "";
		}

		$this->sql = ' INSERT INTO ' . $this->table . ' ( ' . $sql . $system_columns . ' ) values ( ' . $sql_values . $system_values . ' ) ';
		$statement = $this->dbh->prepare($this->sql);
		foreach ($data as $key => $dati) {
			if ($dati === 'NULL') {
				$statement->bindValue($key, null, PDO::PARAM_NULL);
			} else {
				$statement->bindValue($key, $dati);
			}
		}
		try {
			$this->script = $statement->execute();
		} catch (Exception $e) {
			$this->script = $this->handleDBError($e, $statement);
		}
		if (empty($this->is_transaction)) {
			$get_params = array($this->primary_key_column => $this->dbh->lastInsertId());
			if (!empty($option_is_set)) {
				$get_params["with"] = "options";
			}
			static::setUseReader(false);
			$this->get($get_params);
		}

		return $this;
	}

	/********************************************************************
	 * UPDATE function
	 *******************************************************************
	 * @throws \Exception
	 */

	public function update($params = array())
	{
		if (empty($this->dbh)) {
			return $this;
		}

		$sql = '';
		$sql_values = '';
		$data = array();
		$this->data_types = unserialize(__OBRAY_DATATYPES__);

		$this->getWorkingDef();

		foreach ($params as $key => $param) {

			if (isset($this->table_definition[$key])) {

				$def = $this->table_definition[$key];
				if (!empty($def["options"])) {
					$options = array_change_key_case($def["options"], CASE_LOWER);
					if (!empty($options[strtolower($param)]) && !is_array($options[strtolower($param)])) {
						$data[$key] = $options[strtolower($param)];
						$option_is_set = TRUE;
					} else {
						$data[$key] = $param;
					}
				} else {
					$data[$key] = $param;
				}
				$data_type = $this->getDataType($def);

				if (isset($def['required']) && $def['required'] === TRUE && (!isset($param) || $param === NULL || $param === '')) {
					$this->throwError(($def['error_message'] ?? isset($def['label'])) ? $def['label'] . ' is required.' : $key . ' is required.', 500, $key);
				}

				if ((isset($def['data_type']) && !empty($this->data_types[$data_type['data_type']]['validation_regex']) && !preg_match($this->data_types[$data_type['data_type']]['validation_regex'], $param)) && $param == NULL) {
					$this->throwError(($def['error_message'] ?? isset($def['label'])) ? $def['label'] . ' is invalid.' : $key . ' is invalid.', 500, $key);
				}

				if (isset($def['data_type']) && $def['data_type'] == 'password') {
					$salt = '$2a$12$' . $this->generateToken();
					$data[$key] = crypt($param, $salt);
				}

				if (!empty($sql)) {
					$sql .= ',';
					$sql_values .= ',';
				}
				$sql .= $key . ' = :' . $key . ' ';

			}
		}

		if (empty($this->primary_key_column)) {
			$this->throwError('Please specify a primary key.', 'primary_key', '500');
		}
		if (!isset($params[$this->primary_key_column])) {
			$this->throwError('Please specify a value for the primary key.', '500', $this->primary_key_column);
		}
		if ($this->isError()) {
			return $this;
		}


		if ($this->enable_system_columns) {
			if (isset($_SESSION['ouser']->ouser_id) && !empty($_SESSION['ouser']->ouser_id)) {
				$omu = $_SESSION['ouser']->ouser_id;
			} else {
				$omu = 0;
			}
			$system_columns = ', OMDT = \'' . date('Y-m-d H:i:s') . '\', OMU = ' . $omu;

		} else {
			$system_columns = "";
		}

		$this->sql = ' UPDATE ' . $this->table . ' SET ' . $sql . $system_columns . ' WHERE ' . $this->primary_key_column . ' = :' . $this->primary_key_column . ' ';
		$statement = $this->dbh->prepare($this->sql);
		foreach ($data as $key => $datum) {
			if ($datum == 'NULL') {
				$statement->bindValue($key, null, PDO::PARAM_NULL);
			} else {
				$statement->bindValue($key, $datum);
			}
		}
		try {
			$this->script = $statement->execute();
		} catch (Exception $e) {
			$this->script = $this->handleDBError($e, $statement);
		}

		if (empty($this->is_transaction)) {
			$get_params = array($this->primary_key_column => $params[$this->primary_key_column]);
			if (!empty($option_is_set)) {
				$get_params["with"] = "options";
			}
			static::setUseReader(false);
			$this->get($get_params);
		}

		return $this;
	}

	/********************************************************************
	 *
	 * DELETE function
	 *******************************************************************
	 * @throws \Exception
	 */

	public function delete($params = array())
	{
		if (empty($this->dbh)) {
			return $this;
		}
		$this->where = $this->getWhere($params, $values);

		if (empty($this->where)) {
			$this->throwError('Please provide a filter for this delete statement', 500);
		}
		if (!empty($this->errors)) {
			return $this;
		}

		$this->sql = ' DELETE FROM ' . $this->table . $this->where;
		$statement = $this->dbh->prepare($this->sql);
		foreach ($values as $value) {
			if (is_integer($value)) {
				$statement->bindValue($value['key'], trim($value['value']), PDO::PARAM_INT);
			} else {
				$statement->bindValue($value['key'], trim((string)$value['value']), PDO::PARAM_STR);
			}
		}
		try {
			$this->script = $statement->execute();
		} catch (Exception $e) {
			$this->script = $this->handleDBError($e, $statement);
		}

		return $this;
	}

	/********************************************************************
	 *
	 * GET function
	 *******************************************************************
	 * @throws \Exception
	 */

	public function get($params = array())
	{
		$original_params = $params;

		if (!empty($this->enable_system_columns)) {
			$this->table_definition['OCDT'] = array('data_type' => 'datetime');
			$this->table_definition['OMDT'] = array('data_type' => 'datetime');
			$this->table_definition['OCU'] = array('data_type' => 'integer');
			$this->table_definition['OMU'] = array('data_type' => 'integer');
		}

		$limit = '';
		$order_by = '';
		$filter = TRUE;
		if (isset($params['start']) && isset($params['rows'])) {
			$limit = ' LIMIT ' . $params['start'] . ',' . $params['rows'] . '';
			unset($params['start']);
			unset($params['rows']);
			unset($original_params['start']);
			unset($original_params['rows']);
		}
		if (isset($params['filter']) && $params['filter'] == 'false') {
			$filter = FALSE;
			unset($params['filter']);
		}
		if (isset($params['order_by'])) {
			$order_by = explode('|', $params['order_by']);
			$columns = array();
			foreach ($order_by as &$order) {
				$order = explode(':', $order);
				if (!empty($order) && array_key_exists($order[0], $this->table_definition)) {
					$columns[] = $order[0];
					if (count($order) > 1) {
						switch ($order[1]) {
							case 'ASC':
							case 'asc':
								$columns[count($columns) - 1] .= ' ASC ';
								break;
							case 'DESC':
							case 'desc':
								$columns[count($columns) - 1] .= ' DESC ';
								break;
						}
					}
				}
			}
			if (!empty($columns)) {
				$order_by = ' ORDER BY ' . implode(',', $columns);
			} else {
				$order_by = '';
			}
		}

		$withs = array();
		$original_withs = array();

		if (!empty($params['with'])) {
			$withs = explode('|', $params['with']);
			$original_withs = $withs;
		}

		$columns = array();
		$withs_to_pass = array();

		foreach ($this->table_definition as $column => $def) {
			if (isset($def['data_type']) && $def['data_type'] == "filter") {
				continue;
			}
			if (isset($def['data_type']) && $def['data_type'] == 'password' && isset($params[$column])) {
				$password_column = $column;
				$password_value = $params[$column];
				unset($params[$column]);
			}
			$columns[] = $this->table . '.' . $column;

			// HANDLE OPTIONS
			if (!empty($params[$column]) && !empty($def["options"])) {
				$options = $def["options"];
				$options = array_change_key_case($options, CASE_LOWER);
				if (!empty($options[strtolower($params[$column])])) {
					$params[$column] = $options[strtolower($params[$column])];
				}
			}

			foreach ($withs as $i => &$with) {
				if (!is_array($with) && array_key_exists($with, $def)) {
					unset($original_withs[$i]);
					$name = $with;
					if (!is_array($def[$with])) {
						$with = explode(':', $def[$with]);
					} else {
						$with = array();
					}
					$with[] = $column;
					$with[] = $name;
				}
			}
		}

		$filter_join = "";
		foreach ($withs as $i => $w) {
			if (!is_array($w)) {
				$withs_to_pass[] = $w;
				unset($withs[(int)$i]);
			}
		}
		$withs = array_values($withs);
		$withs_to_pass = http_build_query(array('with' => implode('|', $withs_to_pass)));
		foreach ($withs as &$with) {
			if (strpos($with[1], 'with') === FALSE) {
				if (strpos($with[1], '?') === FALSE) {
					$with[1] .= '?' . $withs_to_pass;
				} else {
					$with[1] .= '&' . $withs_to_pass;
				}
			}
		}

		if (isset($original_params['with'])) {
			$original_params['with'] = implode('|', $original_withs);
		}
		$values = array();
		$where_str = $this->getWhere($params, $values, $original_params);

		$this->sql = 'SELECT ' . implode(',', $columns) . ' FROM ' . $this->table . $this->getJoin() . $filter_join . $where_str . $order_by . $limit;
		$statement = (!empty($this->reader) && static::getShouldUseReader()) ? $this->reader->prepare($this->sql) : $this->dbh->prepare($this->sql);
		foreach ($values as $value) {
			if (is_integer($value)) {
				$statement->bindValue($value['key'], trim($value['value']), PDO::PARAM_INT);
			} else {
				$statement->bindValue($value['key'], trim((string)$value['value']), PDO::PARAM_STR);
			}
		}
		try {
			$statement->execute();
		} catch (Exception $e) {
			$this->handleDBError($e, $statement);
		}
		$statement->setFetchMode(PDO::FETCH_NUM);
		$data = $statement->fetchAll(PDO::FETCH_OBJ);

		$this->data = $data;

		if (!empty($withs) && !empty($this->data)) {

			foreach ($withs as &$with) {

				// HANDLES OPTIONS
				if (strpos($with[1], "options?with") !== FALSE) {
					if (!empty($this->table_definition[$with[0]]["options"])) {
						$column = $with[0];
						$options = $this->table_definition[$with[0]]["options"];
						foreach ($this->data as $key => $data) {
							$option = array_search($data->$column, $options);
							if ($option !== FALSE) {
								$this->data[$key]->$column = $option;
							}
						}
					}
					continue;
				}


				$ids_to_index = array();
				if (!is_array($with)) {
					break;
				}
				$with_key = $with[0];
				$with_column = $with[2];
				$with_name = $with[3];
				$with_components = parse_url($with[1]);
				$sub_params = array();
				foreach ($this->data as $i => $data) {
					if (!isset($ids_to_index[$data->$with_column])) {
						$ids_to_index[$data->$with_column] = array();
					}
					$ids_to_index[$data->$with_column][] = (int)$i;
				}
				$ids = array();
				foreach ($this->data as $row) {
					$ids[] = $row->$with_column;
				}
				$ids = implode('|', $ids);
				if (!empty($with_components['query'])) {
					parse_str($with_components['query'], $sub_params);
				}
				if ($ids !== '') {
					$with[0] = $with[0] . '=' . $ids;
				} else {
					$with[0] = $with[0] . '=';
				}
				if (isset($original_params['with']) && empty($original_params['with'])) {
					unset($original_params['with']);
				}
				if (!empty($original_params['with']) && !empty($sub_params['with'])) {
					$original_params['with'] = array_unique(array_merge(explode('|', $sub_params['with']), explode('|', $original_params['with'])));
					$original_params['with'] = implode('|', $original_params['with']);
				}
				$sub_params = array_replace($sub_params, $original_params);
				$new_params = array();
				parse_str($with[0], $new_params);
				$sub_params = array_replace($sub_params, $new_params);

				if (in_array('children', $withs[0])) {
					$sub_params['with'] = 'children';
				}
				$with = $this->route($with_components['path'] . 'get/', $sub_params)->data;
				foreach ($with as $w) {
					if (isset($ids_to_index[$w->$with_key])) {
						foreach ($ids_to_index[$w->$with_key] as $index) {
							if (!isset($this->data[$index]->$with_name)) {
								$this->data[$index]->$with_name = array();
							}
							array_push($this->data[$index]->$with_name, $w);
						}
					}
				}


				if ($filter) {
					foreach ($this->data as $i => $data) {
						if (empty($data->$with_name)) {
							unset($this->data[$i]);
						}
					}
					$this->data = array_values((array)$this->data);
				}

			}

		}

		if ($this->table == 'ousers' || (isset($this->user_session) && $this->table == $this->user_session)) {
			foreach ($this->data as $i => &$data) {
				if (isset($password_column) && strcmp($data->$password_column, crypt($password_value ?? null, $data->$password_column)) != 0) {
					unset($this->data[$i]);
				}
				unset($data->ouser_password);
			}
		}

		//Restructure the result set to be keyed by the column name provided
		if (!empty($original_params['keyed']) && !empty($this->data[0]->{$original_params['keyed']})) {
			$keyed_data = array();
			foreach ($this->data as $data) {
				if (isset($data->{$original_params['keyed']}))
					$keyed_data[strtolower($data->{$original_params['keyed']})] = $data;
			}

			if (count($keyed_data))
				$this->data = $keyed_data;
		}
		$this->filter = $filter;
		$this->recordcount = count($this->data);

		return $this;
	}

	private function getJoin()
	{
		if (!empty($this->join)) {
			$obj = $this->route($this->join);
			foreach ($obj->table_definition as $key => $def) {
				if (!empty($def["primary_key"]) && $def["primary_key"] === TRUE) {
					$primary_key = $key;
				}
			}
			foreach ($this->table_definition as $key => $def) {
				if (!empty($def["primary_key"]) && $def["primary_key"] === TRUE) {
					$this->primary_key_column = $key;
				}
			}
			// TODO: What if $primary_key is undefined? the SQL will fail, so do we want to throw an exception here?
			return ' INNER JOIN ' . strtolower($obj->table) . ' ON ' . strtolower($obj->table) . '.' . ($primary_key ?? null) . ' = ' . strtolower($this->table) . '.' . $this->primary_key_column . ' ';
		} else {
			return '';
		}
	}

	/********************************************************************
	 *
	 * GETWHERE
	 ********************************************************************/

	private function getWhere(&$params = array(), &$values = array(), &$original_params = array())
	{
		if (!empty($this->enable_system_columns)) {
			$this->table_definition['OCDT'] = array('data_type' => 'datetime');
			$this->table_definition['OMDT'] = array('data_type' => 'datetime');
		}

		$where = array();
		$count = 0;
		foreach ($params as $key => &$param) {
			$original_key = $key;
			$operator = '=';
			switch (substr($key, -1)) {
				case '!':
				case '<':
				case '>':
					$operator = substr($key, -1) . '=';
					//$p[str_replace(substr($key,-1),'',$key)] = $params[$key];
					$key = str_replace(substr($key, -1), '', $key);
				default:
					if (empty($params[$key])) {
						$array = explode('~', $key);
						if (count($array) === 2) {
							$param = $array[1];
							$key = $array[0];
							unset($params[$key]);
							$operator = 'LIKE';
						}
						$array = explode('>', $key);
						if (count($array) === 2) {
							$param = urldecode($array[1]);
							$key = $array[0];
							unset($params[$key]);
							$operator = '>';
						}
						$array = explode('<', $key);
						if (count($array) === 2) {
							$param = urldecode($array[1]);
							$key = $array[0];
							unset($params[$key]);
							$operator = '<';
						}
					}
					break;
			}

			if (array_key_exists($key, $this->table_definition)) {

				if (!is_array($param)) {
					$param = array(0 => $param);
				}

				foreach ($param as $param_value) {

					if (empty($where)) {
						$new_key = '';
					} else {
						$new_key = 'AND';
					}
					$ors = explode('|', $param_value);

					$where[] = array('join' => $new_key . ' (', 'key' => '', 'value' => '', 'operator' => '');
					if ($operator == '=' && count($ors) > 1) {

						$value_keys = array();
						foreach ($ors as $v) {
							++$count;
							$values[] = array('key' => ':' . $key . '_' . $count, 'value' => $v);
							$value_keys[] = ':' . $key . '_' . $count;
						}

						$where[] = array('join' => '', 'key' => $key, 'value' => '(' . implode(',', $value_keys) . ')', 'operator' => 'IN');


					} else {

						$or_key = '';

						foreach ($ors as $v) {

							if ($v !== 'NULL') {
								if ($operator == 'LIKE') {
									$v = '%' . $v . '%';
								}
								++$count;
								$values[] = array('key' => ':' . $key . '_' . $count, 'value' => $v);
								$where[] = array('join' => $or_key, 'key' => $key, 'value' => ':' . $key . '_' . $count, 'operator' => $operator);
								$or_key = 'OR';
							} else {
								$where[] = array('join' => $or_key, 'key' => $key, 'value' => ' IS NULL ', 'operator' => '');
							}

						}

					}
					$where[] = array('join' => ')', 'key' => '', 'value' => '', 'operator' => '');
				}
			}

			if (!empty($original_params) && $key == 'OMDT') {
				unset($original_params[$original_key]);
			}
			if (!empty($original_params) && $key == 'OCDT') {
				unset($original_params[$original_key]);
			}

		}

		$where_str = '';
		if (!empty($where)) {
			$where_str = ' WHERE ';
			foreach ($where as $value) {
				$where_str .= ' ' . $value['join'] . ' ' . $value['key'] . ' ' . $value['operator'] . ' ' . $value['value'] . ' ';
				//if( $value['operator'] == '!=' ){ $where_str .= ' OR '.$value['key'].' IS NULL '; }
			}
		}

		return $where_str;
	}

	/********************************************************************
	 *
	 * GETDATATYPE
	 ********************************************************************/

	private function getDataType($def)
	{
		if (!isset($def['data_type'])) {
			return FALSE;
		}                                                   // make sure datatype is set
		$data_type = explode('(', $def['data_type']);                                                       // explode datatypes that contain a size i.e. varchar(255)
		if (!isset($data_type[1])) {
			$data_type[1] = '';
		}                                                 // if size is used then extract it
		$data_type[1] = str_replace(')', '', $data_type[1]);                                                   // remove extra ')' and extract data type
		return array('data_type' => $data_type[0], 'size' => $data_type[1]);                                       // return datatype with size
	}

	/********************************************************************
	 *
	 * GETSLUG
	 ********************************************************************/

	private function getSlug($slug, $column, $parent)
	{
		$count = 1;
		$i = 0;
		while ($count > 0) {
			$new_slug = $slug;
			if ($i == 0) {
				$appendage = '';
			} else {
				$appendage = ' ' . $i;
			}
			$params = array('slug' => strtolower(removeSpecialChars(str_replace('-' . ($i - 1), '', $new_slug) . $appendage, '-', 'and')));
			if (!empty($parent) && isset($this->parent_column)) {
				$parent_sql = ' AND ' . $this->parent_column . ' = :' . $this->parent_column . ' ';
				$params[$this->parent_column] = $parent;
			} else {
				$parent_sql = '';
			}
			$sql = ' SELECT ' . $column . ' FROM ' . $this->table . ' WHERE ' . $this->slug_value_column . ' = :slug ' . $parent_sql . ' ';
			$statement = $this->dbh->prepare($sql);
			$statement->execute($params);
			$count = count($statement->fetchAll());
			++$i;
		}
		return $params['slug'] ?? null;
	}

	/********************************************************************
	 *
	 * SORT
	 ********************************************************************/

	public function sort($column, $order = 'asc', $with = null, $query = '')
	{
		parse_str($query, $this->params);
		$this->column = $column;
		$this->order = $order;
		if (empty($with)) {
			$this->with = array();
		} else {
			$this->with = explode('|', $with);
		}

		usort($this->data, array($this, 'sortCallback'));

		return $this;
	}

	/**
	 * @param $a
	 * @param $b
	 * @return bool|void
	 */
	private function sortCallback($a, $b)
	{
		$column = $this->column;

		$with_array = $this->with;
		if (!empty($this->with)) {

			$with = array_shift($with_array);

			if (empty($a->$with) || empty($b->$with)) {
				return FALSE;
			}
			$filters_a = $a->$with;
			$filters_b = $b->$with;

			$final_a = new stdClass();
			foreach ($filters_a as $a) {
				foreach ($this->with as $with) {
					if (!empty($a->$with)) {
						foreach ($a->$with as $a_item) {
							foreach ($this->params as $key => $value) {
								if (!empty($a_item->$key) && $a_item->$key == $value) {
									$final_a = $a_item;
								}
							}
						}
					}
				}
			}

			$final_b = new stdClass();
			foreach ($filters_b as $b) {
				foreach ($this->with as $with) {
					if (!empty($b->$with)) {
						foreach ($b->$with as $b_item) {
							foreach ($this->params as $key => $value) {
								if (!empty($b_item->$key) && $b_item->$key == $value) {
									$final_b = $b_item;
								}
							}
						}
					}
				}
			}

		}

		if (empty($final_a->$column)) {
			return FALSE;
		}
		if (empty($final_b->$column)) {
			return TRUE;
		}

		$a = $final_a->$column;
		$b = $final_b->$column;

		switch ($this->order) {
			case 'asc':
			case 'ASC':
				if ($a > $b) {
					return TRUE;
				} else {
					return FALSE;
				}
			case 'desc':
			case 'DESC':
				if ($a < $b) {
					return TRUE;
				} else {
					return FALSE;
				}
		}
	}

	/********************************************************************
	 *
	 * GETFIRST
	 ********************************************************************/

	public function getFirst()
	{
		if (empty($this->errors)) {
			if (!isset($this->data) || !is_array($this->data)) {
				$this->data = array();
			}
			return reset($this->data);
		} else {
			return 0;
		}
	}

	/********************************************************************
	 *
	 * RUN
	 ********************************************************************/

	public function run($sql, $bind = array(), $forceReader = false)
	{
		if (is_array($sql)) {
			$sql = $sql["sql"];
		}
		try {
			$isSelect = false;
			if (preg_match("/^select/i", $sql)) $isSelect = true;
			$statement = ($forceReader && !empty($this->reader)) ? $this->reader->prepare($sql) : $this->dbh->prepare($sql);
			try {
				$result = $statement->execute($bind);
			} catch (Exception $e) {
				$result = $this->handleDBError($e, $statement);
			}
			$this->data = [];
			if ($isSelect) {
				$statement->setFetchMode(PDO::FETCH_OBJ);
				while ($row = $statement->fetch()) {
					$this->data[] = $row;
				}
			} else {
				$this->data = $result;
			}
		} catch (Exception $e) {
			if (isset($this->is_transaction) && $this->is_transaction) {
				$this->rollbackTransaction();
			}
			$this->throwError($e);
			$this->logError(oCoreProjectEnum::ODBO, $e);
		}
		return $this;
	}

	public function explain($sql)
	{
		$this->console('EXPLAIN ' . $sql);

		try {

			$result = $this->dbh->query('EXPLAIN ' . $sql);
			foreach ($result as $r) {
				$this->console($r);
			}

		} catch (Exception $e) {
			if (isset($this->is_transaction) && $this->is_transaction) {
				$this->rollbackTransaction();
			}
			$this->throwError($e);
			$this->logError(oCoreProjectEnum::ODBO, $e);
		}

		return $this;
	}

	/********************************************************************
	 *
	 * runStoredProc
	 ********************************************************************/

	public function runStoredProc($proc, $params = array())
	{
		$this->data = [];
		$paramString = "";
		$paramCount = 0;
		foreach ($params as $paramName => $paramValue) {
			if ($paramCount > 0) {
				$paramString .= ",";
			}
			$paramString .= ":" . $paramName;
			$paramCount++;
		}

		$procString = "CALL " . $proc . "(" . $paramString . ")";
		$statement = $this->dbh->prepare($procString);
		if ($paramCount > 0) {
			foreach ($params as $paramName => $paramValue) {
				$statement->bindValue(':' . $paramName, $paramValue);
			}
		}

		try {
			$statement->execute();
			$statement->setFetchMode(PDO::FETCH_OBJ);
			$this->data = $statement->fetchAll();
		} catch (Exception $e) {
			if (isset($this->is_transaction) && $this->is_transaction) {
				$this->rollbackTransaction();
			}
			$this->throwError($e);
			$this->logError(oCoreProjectEnum::ODBO, $e);
		}
		return $this->data;
	}

	/********************************************************************
	 *
	 * COUNT
	 *******************************************************************
	 * @throws \Exception
	 */

	public function count($params = array())
	{
		$values = array();
		$where_str = $this->getWhere($params, $values);
		$this->sql = 'SELECT COUNT(*) as count FROM ' . $this->table . ' ' . $where_str;
		$statement = $this->dbh->prepare($this->sql);
		foreach ($values as $value) {
			if (is_integer($value)) {
				$statement->bindValue($value['key'], trim($value['value']), PDO::PARAM_INT);
			} else {
				$statement->bindValue($value['key'], trim((string)$value['value']), PDO::PARAM_STR);
			}
		}
		try {
			$statement->execute();
		} catch (Exception $e) {
			$this->handleDBError($e, $statement);
		}
		while ($row = $statement->fetch()) {
			$this->data[] = $row;
		}
		$this->data = $this->data[0];
		unset($this->data[0]);
		return $this;
	}

	/********************************************************************
	 *
	 * RAND
	 *******************************************************************
	 * @throws \Exception
	 */

	public function random($params = array())
	{
		if (!empty($params['rows']) && is_numeric($params['rows'])) {
			$rows = $params['rows'];
		} else {
			$rows = 1;
		}
		$values = array();
		$where_str = $this->getWhere($params, $values);
		$statement = $this->dbh->prepare('SELECT * FROM ' . $this->table . ' ' . $where_str . ' ORDER BY RAND() LIMIT ' . $rows);
		foreach ($values as $value) {
			if (is_integer($value)) {
				$statement->bindValue($value['key'], trim($value['value']), PDO::PARAM_INT);
			} else {
				$statement->bindValue($value['key'], trim((string)$value['value']), PDO::PARAM_STR);
			}
		}
		try {
			$statement->execute();
		} catch (Exception $e) {
			$this->handleDBError($e, $statement);
		}
		$statement->setFetchMode(PDO::FETCH_NUM);
		$this->data = $statement->fetchAll(PDO::FETCH_OBJ);
		return $this;
	}

	/********************************************************************
	 *
	 * MATH FUNCTIONS
	 ********************************************************************/

	public function sum($params = array())
	{
		$this->math('SUM', 'sum', $params);
	}

	public function average($params = array())
	{
		$this->math('AVG', 'average', $params);
	}

	public function maximum($params = array())
	{
		$this->math('MAX', 'maximum', $params);
	}

	public function minimum($params = array())
	{
		$this->math('MIN', 'minimum', $params);
	}

	public function truncate()
	{
		$statement = $this->dbh->prepare('TRUNCATE TABLE ' . $this->table);
		try {
			$statement->execute();
		} catch (Exception $e) {
			$this->handleDBError($e, $statement);
		}
	}

	private function math($fn, $key, $params = array())
	{

		$column = $params['column'];
		unset($params['column']);
		if (array_key_exists($column, $this->table_definition)) {
			$values = array();
			$where_str = $this->getWhere($params, $values);
			$statement = $this->dbh->prepare('SELECT ' . $fn . '(' . $column . ') as ' . $key . ' FROM ' . $this->table . ' ' . $where_str);
			foreach ($values as $value) {
				if (is_integer($value)) {
					$statement->bindValue($value['key'], trim($value['value']), PDO::PARAM_INT);
				} else {
					$statement->bindValue($value['key'], trim((string)$value['value']), PDO::PARAM_STR);
				}
			}
			try {
				$statement->execute();
			} catch (Exception $e) {
				$this->handleDBError($e, $statement);
			}
			while ($row = $statement->fetch()) {
				$this->data[] = $row;
			}
			$this->data = $this->data[0];
			unset($this->data[0]);
		} else {
			$this->throwError('Column does not exist.');
		}
		return $this;
	}

	/********************************************************************
	 *
	 * UNIQUE
	 *******************************************************************
	 * @throws \Exception
	 */

	public function unique($params = array())
	{
		$column = $params['column'];
		unset($params['column']);

		if (array_key_exists($column, $this->table_definition)) {
			$values = array();
			$where_str = $this->getWhere($params, $values);
			$statement = $this->dbh->prepare('SELECT DISTINCT ' . $column . ' FROM ' . $this->table . ' ' . $where_str);
			foreach ($values as $value) {
				if (is_integer($value)) {
					$statement->bindValue($value['key'], trim($value['value']), PDO::PARAM_INT);
				} else {
					$statement->bindValue($value['key'], trim((string)$value['value']), PDO::PARAM_STR);
				}
			}
			try {
				$statement->execute();
			} catch (Exception $e) {
				$this->handleDBError($e, $statement);
			}
			while ($row = $statement->fetch()) {
				$this->data[] = $row[$column];
			}
			return $this;
		} else {
			$this->throwError('Column does not exist.');
			return $this;
		}
	}

	/********************************************************************
	 *
	 * LOG
	 *******************************************************************
	 * @throws \Exception
	 */

	protected function log($object, $label = null)
	{
		if (__OBRAY_DEBUG_MODE__) {
			$sql = 'CREATE TABLE IF NOT EXISTS ologs ( olog_id INT UNSIGNED NOT NULL AUTO_INCREMENT,olog_label VARCHAR(255),olog_data TEXT,OCDT DATETIME,OCU INT UNSIGNED, PRIMARY KEY (olog_id) ) ENGINE=' . __OBRAY_DATABASE_ENGINE__ . ' DEFAULT CHARSET=' . __OBRAY_DATABASE_CHARACTER_SET__ . '; ';
			$statement = $this->dbh->prepare($sql);
			$statement->execute();
		}

		$sql = 'INSERT INTO ologs(olog_label,olog_data,OCDT,OCU) VALUES(:olog_label,:olog_data,:OCDT,:OCU);';
		$statement = $this->dbh->prepare($sql);
		$statement->bindValue('olog_label', $label, PDO::PARAM_STR);
		$statement->bindValue('olog_data', json_encode($object, JSON_PRETTY_PRINT), PDO::PARAM_STR);
		$statement->bindValue('OCDT', date('Y-m-d H:i:s'), PDO::PARAM_STR);
		$statement->bindValue('OCU', $_SESSION['ouser']->ouser_id ?? 0, PDO::PARAM_INT);
		try {
			$statement->execute();
		} catch (Exception $e) {
			$this->handleDBError($e, $statement);
		}
	}

	/********************************************************************
	 *
	 * GENERATETOKEN
	 ********************************************************************/

	private function generateToken()
	{
		$safe = FALSE;
		return hash('sha512', base64_encode(openssl_random_pseudo_bytes(128, $safe)));
	}

	/**
	 * Handle DB Error
	 *
	 * Handles erros from PDO, attempt to correct failed DB connections and retries, otherwise
	 * raises new exception.
	 * @throws \Exception
	 */

	public function handleDBError(Exception $e, $statement, $count = 1)
	{
		if ($count >= 3) {
			throw new Exception("Failed $count times", 0, $e);
		}
		$errors = [
			'server has gone away',
			'no connection to the server',
			'Lost connection',
			'is dead or not enabled',
			'Error while sending',
			'decryption failed or bad record mac',
			'server closed the connection unexpectedly',
			'SSL connection has been closed unexpectedly',
			'Error writing data to the connection',
			'Resource deadlock avoided',
			'Transaction() on null',
			'child connection forced to terminate due to client_idle_limit',
			'query_wait_timeout',
			'reset by peer',
			'Physical connection is not usable',
			'TCP Provider: Error code 0x68',
			'Name or service not known'
		];

		foreach ($errors as $error) {
			if (strpos($e->getMessage(), $error) !== false) {
				$this->dbh = getDatabaseConnection(true);
				$this->reader = getReaderDatabaseConnection(true);
				try {
					return $statement->execute();
				} catch (Exception $e) {
					return $this->handleDBError($e, $statement, ++$count);
				}
			}
		}

		throw new Exception('Unrecoverable PDO error', 0, $e);
	}
}
