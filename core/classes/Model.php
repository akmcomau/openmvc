<?php

namespace core\classes;

use core\classes\exceptions\AutoloaderException;
use core\classes\exceptions\ModelException;

class Model {

	protected $config;
	protected $database;
	protected $logger;

	protected $record = [];

	protected $objects = [];

	protected $table          = NULL;
	protected $primary_key    = NULL;
	protected $columns        = NULL;
	protected $indexes        = [];
	protected $foreign_keys   = [];
	protected $uniques        = [];
	protected $relationships  = [];

	protected $core_models = [
		'Administrator',
		'Customer',
		'Country',
		'State',
		'City',
		'Address',
		'PageCategory',
		'PageCategoryLink',
		'BlockCategory',
		'BlockType',
		'Block',
		'BlockCategoryLink',
	];

	public function __construct(Config $config, Database $database) {
		$this->config   = $config;
		$this->database = $database;
		$this->logger   = Logger::getLogger(get_class($this));
	}

	public function getRecord() {
 		return $this->record;
	}

	public function getCoreModels() {
 		return $this->core_models;
	}

	public function setRecord($record) {
		$this->record = $record;
	}

	public function __set($name, $value) {
		if (isset($this->columns[$this->table.'_'.$name])) {
			$this->record[$this->table.'_'.$name] = $value;
		}
		elseif (isset($this->columns[$name])) {
			$this->record[$name] = $value;
		}
		else {
			throw new ModelException("Undefined model property: $name on ".get_class($this));
		}
	}

	public function __get($name) {
		if (isset($this->columns[$this->table.'_'.$name])) {
			if (isset($this->record[$this->table.'_'.$name])) {
				return $this->record[$this->table.'_'.$name];
			}
			else {
				return NULL;
			}
		}
		elseif (isset($this->columns[$name])) {
			if (isset($this->record[$name])) {
				return $this->record[$name];
			}
			else {
				return NULL;
			}
		}
		else {
			throw new ModelException("Undefined model property: $name on ".get_class($this));
		}
	}

	public function getColumnName($name) {
		if (isset($this->columns[$this->table.'_'.$name])) {
			return $this->table.'_'.$name;
		}
		elseif (isset($this->columns[$name])) {
			return $name;
		}

		foreach ($this->relationships as $table => $data) {
			if ($table == '__common_join__') continue;
			if (in_array($name, $data['where_fields'])) {
				return $name;
			}
		}

		return NULL;
	}


	protected function callHook($name) {
		$name = $this->table.'_'.$name;
		$modules = (new Module($this->config))->getEnabledModules();
		foreach ($modules as $module) {
			if (isset($module['hooks']['models'][$name])) {
				$class = $module['namespace'].'\\'.$module['hooks']['models'][$name];
				$this->logger->debug("Calling Hook: $class::$name");
				$class = new $class($this->config, $this->database, NULL);
				return call_user_func_array(array($class, $name), [$this]);
			}
		}
	}

	public function insert() {
		$table       = $this->table;
		$primary_key = $this->primary_key;

		$columns = [];
		$values  = [];
		foreach (array_keys($this->columns) as $column) {
			if (!isset($this->record[$column]) && $column == "{$table}_created") {
				$columns[] = $column;
				$values[]  = 'NOW()';
			}
			elseif ($column != $primary_key && array_key_exists($column, $this->record)) {
				$columns[] = $column;
				$values[]  = $this->database->quote($this->record[$column]);
			}
		}

		$sql = "INSERT INTO $table (".join(',', $columns).") VALUES (".join(',', $values).")";
		$this->database->executeQuery($sql);

		if ($this->database->getEngine() == 'pgsql') {
			$sql = "SELECT currval(pg_get_serial_sequence('$table', '$primary_key'))";
			$this->record[$primary_key] = $this->database->queryValue($sql);
		}
		else {
			$this->record[$primary_key] = $this->lastInsertId();
		}
		$this->logger->info("Inserted record in $table => ".$this->record[$primary_key]);

		$this->callHook('insert');

		return $this->record[$primary_key];
	}

	public function update() {
		$table       = $this->table;
		$primary_key = $this->primary_key;

		if (!isset($this->record[$primary_key])) {
			throw new ModelException("Object has no primary key: ".print_r($this, TRUE));
		}

		$values  = [];
		foreach (array_keys($this->columns) as $column) {
			if ($column != $primary_key && array_key_exists($column, $this->record)) {
				$values[]  = $column.'='.$this->database->quote($this->record[$column]);
			}
		}

		$sql = "UPDATE $table SET ".join(',', $values)." WHERE $primary_key = ".$this->database->quote($this->record[$primary_key]);
		$this->database->executeQuery($sql);

		$this->callHook('update');
	}

	public function delete() {
		$table       = $this->table;
		$primary_key = $this->primary_key;

		if (!isset($this->record[$primary_key])) {
			throw new ModelException("Object has no primary key: ".print_r($this, TRUE));
		}

		$sql = "DELETE FROM $table WHERE $primary_key = ".$this->database->quote($this->record[$primary_key]);
		$this->database->executeQuery($sql);

		$this->callHook('delete');
	}

	public function get(array $params) {
		$table  = $this->generateFromClause($params);
		$where  = $this->generateWhereClause($params);
		if (strlen($where)) $where = "WHERE $where";
		$sql    = "SELECT ".$this->table.".* FROM $table $where";
		if (isset($params['get_random_record'])) {
			$sql .= " ORDER BY RANDOM() LIMIT 1";
		}
		$record = $this->database->querySingle($sql);
		if ($record) {
			return $this->getModel(get_class($this), $record);
		}
		else {
			return NULL;
		}
	}

	public function getCount(array $params = NULL) {
		$table = $this->table;
		$sql   = "SELECT COUNT(*) as cnt FROM ".$this->generateFromClause($params);
		if ($params) {
			$where = $this->generateWhereClause($params);
			if ($where) {
				$sql .= " WHERE $where";
			}
		}
		return $this->database->queryValue($sql);
	}

	public function getMulti(array $params = NULL, array $ordering = NULL, array $pagination = NULL) {
		$table = $this->table;
		$sql   = "SELECT $table.* FROM ".$this->generateFromClause($params, $ordering);
		if ($params) {
			$where = $this->generateWhereClause($params);
			if ($where) {
				$sql .= " WHERE $where";
			}
		}
		if ($ordering && count($ordering)) {
			$ordering_sql = [];
			foreach ($ordering as $column => $direction) {
				$direction = (strtolower($direction) == 'asc') ? 'ASC' : 'DESC';
				$column = $this->getColumnName($column);
				if ($column) {
					$ordering_sql[] = "$column $direction";
				}
			}
			if (count($ordering_sql)) {
				$sql  .= " ORDER BY ".join(',', $ordering_sql);
			}
		}
		if ($pagination) {
			$sql .= " OFFSET ".(int)$pagination['offset']." LIMIT ".(int)$pagination['limit'];
		}
		$records = $this->database->queryMulti($sql);

		$models = [];
		foreach ($records as $record) {
			$models[] = $this->getModel(get_class($this), $record);
		}

		return $models;
	}

	public function getMultiKeyed($key, array $params = NULL, array $ordering = NULL) {
		$table = $this->table;
		$sql   = "SELECT $table.* FROM ".$this->generateFromClause($params, $ordering);
		if ($params) {
			$where = $this->generateWhereClause($params);
			if ($where) {
				$sql .= " WHERE $where";
			}
		}
		$records = $this->database->queryMulti($sql);

		$models = [];
		foreach ($records as $record) {
			$models[$record[$key]] = $this->getModel(get_class($this), $record);
		}

		return $models;
	}

	public function generateFromClause(array $params = NULL, array $ordering = NULL) {
		if (!$params) $params = [];
		if (!$ordering) $ordering = [];
		$tables = [ $this->table ];
		$in_from = [];
		foreach (array_merge($params, $ordering) as $column => $value) {
			foreach ($this->relationships as $table => $data) {
				if ($table == '__common_join__') continue;
				if (!isset($in_from[$table]) && in_array($column, $data['where_fields'])) {
					if (isset($data['join_clause'])) {
						$tables[] = $data['join_clause'];
						$in_from[$table] = 1;
					}
					elseif (!isset($in_from['__common_join__'])) {
						$tables[] = $this->relationships['__common_join__'];
						$in_from['__common_join__'] = 1;
					}
				}
			}
		}
		return join(' ', $tables);
	}

	public function generateWhereClause(array $params) {
		$where = [];
		foreach ($params as $column => $value) {
			if (isset($this->columns[$this->table.'_'.$column])) {
				$column = $this->table.'.'.$this->table.'_'.$column;
			}
			elseif (isset($this->columns[$column])) {
				$column = $this->table.'.'.$column;
			}
			else {
				$found = FALSE;
				foreach ($this->relationships as $table => $data) {
					if ($table == '__common_join__') continue;
					if (in_array($column, $data['where_fields'])) {
						$found = TRUE;
						$column = "$table.$column";
					}
				}

				if (!$found) continue;
			}

			// its not just an equal
			if (is_array($value)) {
				switch ($value['type']) {
					case 'like':
						$where[] = 'LOWER('.$column.') LIKE '.$this->database->quote(strtolower($value['value']));
						break;

					case 'in':
						if ($value['value']) {
							foreach ($value['value'] as &$val) {
								$val = $this->database->quote($val);
							}
							$where[] = $column.' IN ('.join(',', $value['value']).')';
						}
						break;
				}
			}
			else {
				$where[] = $column.'='.$this->database->quote($value);
			}
		}
		return join (' AND ', $where);
	}

	public function getModel($class, array $data = NULL) {
		$model = new $class($this->config, $this->database);
		if ($data) {
			if ($this->logger->isDebugEnabled()) {
				$this->logger->debug("Creating Model: $class => ".json_encode($data));
			}
			$model->setRecord($data);
		}
		return $model;
	}

	public function createDatabase() {
		// Create the tables
		foreach ($this->core_models as $table) {
			$this->logger->info("Creating table: $table");
			$model = $this->getModel("core\\classes\\models\\$table");
			$model->createTable();
		}

		// Create the indexes
		foreach ($this->core_models as $table) {
			$this->logger->info("Creating indexes: $table");
			$model = $this->getModel("core\\classes\\models\\$table");
			$model->createIndexes();
		}

		// Create the foreign keys
		foreach ($this->core_models as $table) {
			$this->logger->info("Creating foreign keys: $table");
			$model = $this->getModel("core\\classes\\models\\$table");
			$model->createForeignKeys();
		}
	}

	public function dropTable() {
		$sql = 'DROP TABLE '.$this->table;
		return $this->database->executeQuery($sql);
	}

	public function createTable() {
		if ($this->database->getEngine() == 'mysql') {
			return $this->createTableMySQL();
		}
		elseif ($this->database->getEngine() == 'pgsql') {
			return $this->createTablePgSQL();
		}
	}

	public function createForeignKeys() {
		if ($this->database->getEngine() == 'mysql') {
			return $this->createForeignKeysMySQL();
		}
		elseif ($this->database->getEngine() == 'pgsql') {
			return $this->createForeignKeysPgSQL();
		}
	}

	public function createIndexes() {
		if ($this->database->getEngine() == 'mysql') {
			return TRUE;
		}
		elseif ($this->database->getEngine() == 'pgsql') {
			return $this->createIndexesPgSQL();
		}
	}

	protected function getDataType($data) {
		if ($this->database->getEngine() == 'mysql') {
			return $this->getDataTypeMySQL($data);
		}
		elseif ($this->database->getEngine() == 'pgsql') {
			return $this->getDataTypePgSQL($data);
		}
	}

	protected function createTableMySQL() {
		// create the table
		$sql = 'CREATE TABLE '.$this->table." (\n";

		// add the columns
		foreach ($this->columns as $column => $data) {
			$sql .= "\t$column ".$this->getDataType($data).",\n";
		}

		// add the indexes
		foreach ($this->indexes as $column) {
			if (is_array($column)) {
				$sql .= "\tINDEX (".join(',', $column)."),\n";
			}
			else {
				$sql .= "\tINDEX ($column),\n";
			}
		}

		// add the uniques
		foreach ($this->uniques as $column) {
			if (is_array($column)) {
				$sql .= "\tUNIQUE INDEX (".join(',', $column)."),\n";
			}
			else {
				$sql .= "\tUNIQUE INDEX ($column),\n";
			}
		}

		// add the primary key
		$sql .= "\tPRIMARY KEY (".$this->primary_key.")\n";

		// make an InnoDB type database
		$sql .= ") ENGINE=InnoDB DEFAULT CHARACTER SET = utf8;\n";

		return $this->database->executeQuery($sql);
	}

	protected function createForeignKeysMySQL() {
		// add the forign keys
		$sql = '';
		foreach ($this->foreign_keys as $column => $foreign) {
			$foreign_table  = $foreign[0];
			$foreign_column = $foreign[1];
			$sql .= "ALTER TABLE ".$this->table." ADD CONSTRAINT FOREIGN KEY ($column) REFERENCES $foreign_table ($foreign_column);";
		}

		if ($sql != '') {
			return $this->database->executeQuery($sql);
		}

		return TRUE;
	}

	protected function getDataTypeMySQL($data) {
		$type = '';
		switch ($data['data_type']) {
			case 'smallint':
				$type = 'SMALLINT';
				break;

			case 'int':
				$type = 'INT';
				break;

			case 'bigint':
				$type = 'BIGINT';
				break;

			case 'numeric':
				$type = 'DECIMAL';
				if (isset($data['data_length']) && $data['data_length']) {
					$type.= '('.join(',', $data['data_length']).')';
				}
				break;

			case 'date':
				$type = 'DATE';
				break;

			case 'datetime':
				$type = 'DATETIME';
				break;

			case 'bool':
				$type = 'BOOL';
				break;

			case 'text':
				if (!isset($data['data_length'])) {
					$type = 'LONGTEXT';
				}
				elseif ((int)$data['data_length'] <= 128) {
					$type = 'CHAR('.(int)$data['data_length'].')';
				}
				elseif ((int)$data['data_length'] <= 256) {
					$type = 'VARCHAR('.(int)$data['data_length'].')';
				}
				elseif ((int)$data['data_length'] <= 65535) {
					$type = 'TEXT';
				}
				elseif ((int)$data['data_length'] <= 16777215) {
					$type = 'MEDIUMTEXT';
				}
				else {
					$type = 'LONGTEXT';
				}
				break;

			case 'blob':
				if (!isset($data['data_length'])) {
					$type = 'LONGBLOB';
				}
				elseif ((int)$data['data_length'] <= 256) {
					$type = 'TINYBLOB';
				}
				elseif ((int)$data['data_length'] <= 65535) {
					$type = 'BLOB';
				}
				elseif ((int)$data['data_length'] <= 16777215) {
					$type = 'MEDIUMBLOB';
				}
				else {
					$type = 'LONGBLOB';
				}
				break;

			default:
				throw new ModelException("Invalid data type: ".$data['data_type']);
				break;
		}

		if (!(isset($data['null_allowed']) && $data['null_allowed'])) {
			$type .= " NOT NULL";
		}

		if (isset($data['auto_increment']) && $data['auto_increment']) {
			$type .= " AUTO_INCREMENT";
		}

		if (isset($data['default_value'])) {
			$type .= " DEFAULT ".$data['default_value'];
		}

		return $type;
	}

	protected function createTablePgSQL() {
		// create the table
		$sql = 'CREATE TABLE '.$this->table." (\n";

		// add the columns
		foreach ($this->columns as $column => $data) {
			$sql .= "\t$column ".$this->getDataType($data).",\n";
		}

		// add the uniques
		foreach ($this->uniques as $column) {
			if (is_array($column)) {
				$sql .= "\tUNIQUE (".join(',', $column)."),\n";
			}
			else {
				$sql .= "\tUNIQUE ($column),\n";
			}
		}

		// add the primary key
		$sql .= "\tPRIMARY KEY (".$this->primary_key.")\n";

		// make an InnoDB type database
		$sql .= ");\n";

		return $this->database->executeQuery($sql);
	}

	protected function createForeignKeysPgSQL() {
		// add the forign keys
		foreach ($this->foreign_keys as $column => $foreign) {
			$foreign_table  = $foreign[0];
			$foreign_column = $foreign[1];
			$sql = "ALTER TABLE ONLY ".$this->table." ADD CONSTRAINT ".$this->table."_".$column."_fk FOREIGN KEY ($column) REFERENCES $foreign_table ($foreign_column);";

			$this->database->executeQuery($sql);
		}
	}

	protected function createIndexesPgSQL() {
		// add the indexes
		foreach ($this->indexes as $column) {
			if (is_array($column)) {
				$sql = "CREATE INDEX ON ".$this->table."(".join(',', $column).");\n";
			}
			else {
				$sql = "CREATE INDEX ON ".$this->table."($column);\n";
			}

			$this->database->executeQuery($sql);
		}
	}

	protected function getDataTypePgSQL($data) {
		$type = '';
		switch ($data['data_type']) {
			case 'smallint':
				if (isset($data['auto_increment']) && $data['auto_increment']) {
					$type = 'SERIAL';
				}
				else {
					$type = 'SMALLINT';
				}
				break;

			case 'int':
				if (isset($data['auto_increment']) && $data['auto_increment']) {
					$type = 'SERIAL';
				}
				else {
					$type = 'INT';
				}
				break;

			case 'bigint':
				if (isset($data['auto_increment']) && $data['auto_increment']) {
					$type = 'SERIAL8';
				}
				else {
					$type = 'BIGINT';
				}
				break;

			case 'numeric':
				$type = 'NUMERIC';
				if (isset($data['data_length']) && $data['data_length']) {
					$data['data_length'][0] += $data['data_length'][1];
					$type.= '('.join(',', $data['data_length']).')';
				}
				break;

			case 'date':
				$type = 'DATE';
				break;

			case 'datetime':
				$type = 'TIMESTAMP WITHOUT time zone';
				break;

			case 'bool':
				$type = 'BOOL';
				break;

			case 'text':
				$type = 'TEXT';
				break;

			case 'blob':
				$type = 'BYTEA';
				break;

			default:
				throw new ModelException("Invalid data type: ".$data['data_type']);
				break;
		}

		if (!(isset($data['null_allowed']) && $data['null_allowed'])) {
			$type .= " NOT NULL";
		}

		if (isset($data['default_value'])) {
			$type .= " DEFAULT ".$data['default_value'];
		}

		return $type;
	}

	public function listAllModels() {
		$site = $this->config->siteConfig();
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$base_core_path = $root_path.'core'.DS.'classes'.DS.'models'.DS;
		$base_site_path = $root_path.'sites'.DS.$site->namespace.DS.'classes'.DS.'models'.DS;
		$base_core_namespace = '\\core\\classes\\models\\';
		$base_site_namespace = '\\sites\\'.$site->namespace.'\\classes\\models\\';

		$models = [];
		foreach (glob("$base_core_path*.php") as $filename) {
			if (preg_match('/\/([\w]+).php$/', $filename, $matches)) {
				$models[] = $base_core_namespace.$matches[1];
			}
		}
		foreach (glob("$base_site_path*.php") as $filename) {
			if (preg_match('/\/([\w]+).php$/', $filename, $matches)) {
				$models[] = $base_site_namespace.$matches[1];
			}
		}

		return $models;
	}

	public function insertInitalData($data_class) {
		try {
			$data_model = $this->getModel($data_class);
			$records    = $data_model->getRecords();
			$this->logger->info("Inserting records for: $data_class");

			foreach ($records as $record) {
				$object = $this->getModel(get_class($this));
				foreach ($record as $property => $value) {
					$object->$property = $value;
				}
				$object->insert();
			}
		}
		catch (AutoLoaderException $ex) {}
	}
}
