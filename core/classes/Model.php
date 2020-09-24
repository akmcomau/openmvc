<?php

namespace core\classes;

use ReflectionClass;
use core\classes\exceptions\AutoloaderException;
use core\classes\exceptions\ModelException;

/**
 * The base database model class.  All database models extend this class.
 */
class Model {
	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The database object
	 * @var Database $database
	 */
	protected $database;

	/**
	 * The logger object
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * The record array of the form @code{.php}[column => value, ...]@endcode
	 * Values from this array are accessed via the __get() function
	 * @var array $record
	 */
	protected $record = [];

	/**
	 * This array is used to cache related model objects to avoid
	 * duplicate database requests.
	 * @var array $objects
	 */
	protected $objects = [];

	/**
	 * This array is used as a static cache related model objects to avoid
	 * duplicate database requests.
	 * @var array $objects
	 */
	protected static $static_objects = [];

	/**
	 * Are results of the get() function cacheable.
	 * This should be TRUE for table where the data very rarely changes,
	 * like the country, state or city tables.
	 * @var boolean $cacheable
	 */
	protected $cacheable      = FALSE;

	/**
	 * The table name this model is associated with.
	 * @var string $table
	 */
	protected $table          = NULL;

	/**
	 * The column name of the primary key for the table
	 * @var string $primary_key
	 */
	protected $primary_key    = NULL;

	/**
	 * The column name of the distribution key for the CitusDB table
	 * @var string $primary_key
	 */
	protected $distribution_key    = NULL;

	/**
	 * During a database creation should this table be created
	 * @var bool $create_schema
	 */
	protected $create_schema  = TRUE;

	/**
	 * Defines which model object this model overrides
	 * @var string $override_model
	 */
	protected $override_model = FALSE;

	/**
	 * Extra data for CitusDB
	 * @var array $objects
	 */
	protected $citusdb = NULL;

	/**
	 * An array containing table columns with data types.<br>
	 * Valid data types: smallint, int, bigint, numeric, time, date, datetime, bool, text, blob <br>
	 * This is an array of the form:
	 * @code{.php}
	 *    $columns = [
	 *       // These fields are valid for all data types
	 *       '*column_name*' => [
	 *           'data_type'      => '*all types*',     // required
	 *           'auto_increment' => *TRUE|FALSE*,      // optional, dafault: FALSE
	 *           'null_allowed'   => *TRUE|FALSE*,      // optional, default: FALSE
	 *           'default_value'  => '*default value*', // optional, default: no default value
	 *
	 *       // these fields are valid only for text or blob data types
	 *       'blob_text_field' => [
	 *           'data_type'      => '*blob|text*',   // required 'blob' or 'text'
	 *           'data_length'    => *num_chars*      // optional, default: no max length
	 *       ],
	 *
	 *       // These fields are valid only for numeric data type
	 *       [
	 *           'data_type' => 'numeric',                 // for numeric/decimal data types
	 *           'data_length' => [*precision*, *scale*]   // number of digits before and after the decimal place
	 *       ],
	 *    ];
	 * @endcode
	 * @var array $columns
	 */
	protected $columns        = NULL;

	/**
	 * An array containing the indexes for the table.
	 * This is an array of the form:
	 * @code{.php}
	 *    $columns = [
	 *       '*column_name1*',
	 *       '*column_name2*',
	 *       ...
	 *    ];
	 * @endcode
	 * @var array $indexes
	 */
	protected $indexes = [];

	/**
	 * An array containing foreign keys for the table.
	 * This is an array of the form:
	 * @code{.php}
	 *    $columns = [
	 *       '*column_name*' => ['*reference_table*', '*reference_column*'],
	 *       ...
	 *    ];
	 * @endcode
	 * @var array $foreign_keys
	 */
	protected $foreign_keys = [];

	/**
	 * An array containing the unique constraints for the table
	 * This is an array of the form:
	 * @code{.php}
	 *    $uniques = [
	 *       '*column_name*',
	 *       ['*column_name1*', '*column_name2*', ...],
	 *       ...
	 *    ];
	 * @endcode
	 * @var array $uniques
	 */
	protected $uniques = [];

	/**
	 * An array containing the partial unique constraints for the table
	 * This is an array of the form:
	 * @code{.php}
	 *    $uniques = [
	 *       ['*condition*', '*column_name*'],
	 *       ['*condition*', '*column_name1*', '*column_name2*', ...],
	 *       ...
	 *    ];
	 * @endcode
	 * @var array $uniques
	 */
	protected $partial_uniques = [];

	/**
	 * An array containing relationships for the table.
	 * This is an array of the form:
	 * @code{.php}
	 *    $relationships = [
	 *       '*reference_table*' => [
	 *          'where_fields' => ['*column_name1*', '*column_name2*', ...],
	 *          'join_clause' => '*join clause*'  // E.g. 'JOIN table USING (table_id)'
	 *       ],
	 *       ...
	 *    ];
	 * @endcode
	 * @var array $relationships
	 */
	protected $relationships = [];

	/**
	 * An array containing the available models for this site.
	 * This is an array of the form:
	 * @code{.php}
	 *    $site_models = [
	 *       '\full\class\name',
	 *       ...
	 *    ];
	 * @endcode
	 * @var array $site_models
	 */
	protected static $site_models;

	/**
	 * The database helper/driver object.
	 * @var DatabaseDriver $sql_helper
	 */
	protected $sql_helper;

	/**
	 * Constructor
	 * @param $config   The configuration object
	 * @param $database The database object
	 */
	public function __construct(Config $config, Database $database) {
		$this->config   = $config;
		$this->database = $database;
		$this->logger   = Logger::getLogger(get_class($this));

		if ($this->config->getSiteDomain()) {
			$this->findAllModels($this->config->siteConfig());
		}
		else {
			$this->findAllModels();
		}

		if (property_exists($this->config->database, 'citusdb') && $this->config->database->citusdb) {
			if (property_exists($this->config->database->citusdb_config, $this->table)) {
				$this->citusdb = $this->config->database->citusdb_config->{$this->table};
			}
		}
	}

	/**
	 * Is this an object that inhertied the model class and represents
	 * a table in the database
	 * @return \b bool TRUE if this object represents a table in the database
	 */
	public function hasTable() {
		return $this->table ? TRUE : FALSE;
	}

	/**
	 * Gets the database table association data
	 * @return \b array The table association data
	 */
	public function getTableData() {
		return [
			'table'           => $this->table,
			'primary_key'     => $this->primary_key,
			'columns'         => $this->columns,
			'indexes'         => $this->indexes,
			'uniques'         => $this->uniques,
			'partial_uniques' => $this->partial_uniques,
			'foreign_keys'    => $this->foreign_keys,
			'citusdb'         => $this->citusdb,
		];
	}

	public function quote($value) {
		if (is_resource($value)) {
			// send out binary data
			$value = bin2hex(stream_get_contents($value));
			$result = "E'\\\\x";
			for($i=0; $i<strlen($value); $i++) {
				$result .= $value[$i];
			}
			$result .= "'";
			return $result;
		}
		elseif ($this->database->getEngine() == 'pgsql' && preg_match('/^E\'\\\\/', $value)) {
			// dont quote escaped values
			return $value;
		}
		return $this->database->quote($value);
	}

	/**
	 * Returns the object to the database helper.  The database helper is a driver
	 * for the database that translates database schema to the Models table association data,
	 * as well as performing database specific queries.
	 * @return \b DatabaseDriver The driver for the database
	 */
	public function sqlHelper() {
		if (!$this->sql_helper) {
			if ($this->database->getEngine() == 'mysql') {
				$this->sql_helper = new database_drivers\MySQL($this->config, $this->database, $this);
			}
			elseif ($this->database->getEngine() == 'pgsql') {
				$this->sql_helper = new database_drivers\PgSQL($this->config, $this->database, $this);
			}
		}

		return $this->sql_helper;
	}

	/**
	 * The magic call function, execute functions on the SQL Helper/Database Driver object
	 * @return \b mixed Returns the result of the function call
	 */
	public function __call ($name, $arguments) {
		$sqlHelper = $this->sqlHelper();
		if (method_exists($sqlHelper, $name)) {
			return call_user_func_array([$sqlHelper, $name], $arguments);
		}

		throw new \ErrorException('Method ' . $name . ' not exists');
	}

	/**
	 * Finds all the available models for this site and stores them in self::$site_models
	 * @param $site  [Optional] Get models for a sepecific site, if NULL then
	 *                  this will find all models for all sites.
	 */
	protected function findAllModels($site = NULL) {
		if (self::$site_models) return self::$site_models;

		$sites = $this->config->sites;
		if ($site) {
			$sites = [ $site ];
		}

		$regex_DS = (DS == '/') ? '\\/' : '\\\\';

		$site_models = [];
		foreach ($sites as $site) {
			$config = clone $this->config;
			$config->setSiteDomain($site->domain, FALSE);

			$site_controllers = [];
			$core_controllers = [];
			$root_path = __DIR__.DS.'..'.DS.'..'.DS;
			$root_path_regex = preg_replace("/$regex_DS/", addslashes($regex_DS), $root_path);

			$dirs = [];
			$dirs[] = $root_path.'core'.DS.'classes'.DS.'models'.DS;
			$dirs[] = $root_path.'sites'.DS.$site->namespace.DS.'classes'.DS.'models'.DS;
			$modules = (new Module($config))->getModules();
			foreach ($modules as $module) {
				if ($module['installed']) {
					$module_path = str_replace('\\', DS, $module['namespace']);
					$dirs[] = $root_path.$module_path.DS.'classes'.DS.'models'.DS;
				}
			}

			foreach ($dirs as $dir) {
				foreach (glob("$dir*.php") as $filename) {
					if (preg_match('/^'.$root_path_regex.'(.*?)'.$regex_DS.'([\w]+)\.php$/', $filename, $matches)) {
						$class = str_replace('/', '\\', $matches[1]).'\\'.$matches[2];
						$site_models[$class] = 1;
					}
				}
			}
		}

		self::$site_models = [];
		foreach ($site_models as $class => $value) {
			$reflectionClass = new ReflectionClass($class);
			if ($reflectionClass->IsInstantiable()) {
				self::$site_models[] = $class;
			}
		}
	}

	/**
	 * Get the record array
	 * @return \b array The record array
	 */
	public function getRecord() {
 		return $this->record;
	}

	/**
	 * Get the create schema value
	 * @return \b bool Should this table be created
	 */
	public function getCreateSchema() {
		return $this->create_schema;
	}

	/**
	 * Get the model class name that this model overrides
	 * @return \b string The overridden class name
	 */
	public function getOverrideModel() {
 		return $this->override_model;
	}

	/**
	 * Get the available models
	 * @return \b array The available models
	 */
	public function getSiteModels() {
 		return self::$site_models;
	}

	/**
	 * Set the record array
	 * @param $record The array to set as the record array
	 */
	public function setRecord(array $record) {
		$this->record = $record;
	}

	/**
	 * Set an element in the objects array
	 * @param $key    The element's key
	 * @param $object The object
	 */
	public function setObjectCache($key, $object) {
		$this->objects[$key] = $object;
	}

	/**
	 * Get an element in the objects array
	 * @param $key     The element's key
	 *
	 * @return $object The cached object
	 */
	public function getObjectCache($key) {
		if (isset($this->objects[$key])) {
			return $this->objects[$key];
		}
		else {
			return NULL;
		}
	}

	/**
	 * Sets an element in the record array
	 * @param $name  The column name
	 * @param $value The value for the column
	 * @throws ModelException if the property does not exist
	 */
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

	/**
	 * Gets an element from the record array
	 * @param $name The column name
	 * @return \b mixed The value of the column
	 * @throws ModelException if the property does not exist
	 */
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

	/**
	 * Translate a column name to the database column name.
	 * @param $name The column name
	 * @return \b string The database column name or NULL if it could not be translated
	 */
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

	/**
	 * Call a database model hook, if one is set
	 * @param $name The name of the hook
	 */
	protected function callHook($name) {
		if (!$this->config->getSiteDomain()) return;

		$name = $this->table.'_'.$name;
		$modules = (new Module($this->config))->getEnabledModules();
		foreach ($modules as $module) {
			if (isset($module['hooks']['models'][$name])) {
				$class = $module['namespace'].'\\'.$module['hooks']['models'][$name];
				$this->logger->debug("Calling Hook: $class::$name");
				$class = new $class($this->config, $this->database, NULL);
				call_user_func_array(array($class, $name), [$this]);
			}
		}
	}

	/**
	 * Insert this model into the database
	 * @return \b mixed The primary key's value
	 */
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
			elseif (array_key_exists($column, $this->record)) {
				$columns[] = $column;
				$values[]  = $this->quote($this->record[$column]);
			}
		}

		$sql = "INSERT INTO $table (".join(',', $columns).") VALUES (".join(',', $values).")";
		$this->database->executeQuery($sql, TRUE);

		if (!isset($this->record[$primary_key]) && !empty($primary_key)) {
			if ($this->database->getEngine() == 'pgsql') {
				$sql = "SELECT currval(pg_get_serial_sequence('$table', '$primary_key'))";
				$this->record[$primary_key] = $this->database->queryValue($sql);
			}
			else {
				$this->record[$primary_key] = $this->database->lastInsertId();
			}
		}
		$this->logger->debug("Inserted record in $table => ".(empty($primary_key) ? 'N/A' : $this->record[$primary_key]));

		$this->callHook('insert');

		if (empty($primary_key)) {
			return -1;
		}
		else {
			return $this->record[$primary_key];
		}
	}

	/**
	 * Update this model in the database
	 * @throws ModelException If the model does not have a primary key set
	 */
	public function update() {
		$table       = $this->table;
		$primary_key = $this->primary_key;
		$distribution_key = $this->distribution_key;

		if (!isset($this->record[$primary_key])) {
			throw new ModelException("Object has no primary key: ".print_r($this, TRUE));
		}

		$values  = [];
		foreach (array_keys($this->columns) as $column) {
			if ($column != $distribution_key && $column != $primary_key && array_key_exists($column, $this->record)) {
				$values[]  = $column.'='.$this->quote($this->record[$column]);
			}
		}

		$sql = "UPDATE $table SET ".join(',', $values)." WHERE $primary_key = ".$this->quote($this->record[$primary_key]);
		$this->database->executeQuery($sql, TRUE);

		$this->callHook('update');
	}

	/**
	 * Delete this model from the database
	 * @throws ModelException If the model does not have a primary key set
	 */
	public function delete() {
		$table       = $this->table;
		$primary_key = $this->primary_key;

		if (!isset($this->record[$primary_key])) {
			throw new ModelException("Object has no primary key: ".print_r($this, TRUE));
		}

		$this->callHook('delete');

		$sql = "DELETE FROM $table WHERE $primary_key = ".$this->quote($this->record[$primary_key]);
		$this->database->executeQuery($sql, TRUE);
	}

	/**
	 * Get a model from the database.  The first record is used to create the model.
	 * @param $params   The params to lookup the record, see generateWhereClause()
	 * @param $ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @return \b Model The model object
	 */
	public function get(array $params, array $ordering = NULL) {
		// check for a cached object
		if ($this->cacheable) {
			$cache_key = md5(get_class($this).print_r($params, TRUE));
			if ($this->database->getCache($cache_key)) {
				return $this->getModel(get_class($this), $this->database->getCache($cache_key));
			}
		}

		$table  = $this->generateFromClause($params, $ordering);
		$where  = $this->generateWhereClause($params);
		if (strlen($where)) $where = "WHERE $where";
		$sql    = "SELECT ".$this->table.".* FROM $table $where";
		if (isset($params['get_random_record'])) {
			$sql .= " ORDER BY RANDOM() LIMIT 1";
		}
		$sql .= $this->getOrderGroupSQL($ordering);
		$record = $this->database->querySingle($sql);
		if ($record) {
			// chache this object
			if ($this->cacheable) {
				$this->database->setCache($cache_key, $record);
			}

			return $this->getModel(get_class($this), $record);
		}
		else {
			return NULL;
		}
	}

	/**
	 * Get a record count from the database
	 * @param $params The params to lookup the record, see generateWhereClause() method
	 * @return \b integer The number of records found
	 */
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

	/**
	 * Get multiple model objects
	 * @param $params The params to lookup the record, see generateWhereClause() method
	 * @param $ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @param $pagination An array of the form @code{.php}['limit' => *limit*[, 'offset' => *offset*]]@endcode
	 * @param $grouping   An array of the form @code{.php}['*column_name1*', '*column_name2*', ...]@endcode
	 * @return \b array An array of model objects
	 */
	public function getMulti(array $params = NULL, array $ordering = NULL, array $pagination = NULL, array $grouping = NULL) {
		$table = $this->table;
		$sql   = "SELECT $table.* FROM ".$this->generateFromClause($params, $ordering);
		if ($params) {
			$where = $this->generateWhereClause($params);
			if ($where) {
				$sql .= " WHERE $where";
			}
		}
		$sql .= $this->getOrderGroupSQL($ordering, $pagination, $grouping);
		$records = $this->database->queryMulti($sql);

		$models = [];
		foreach ($records as $record) {
			$models[] = $this->getModel(get_class($this), $record);
		}

		return $models;
	}

	/**
	 * Get multiple model objects as an associative array
	 * @param $key        The column name of the column to use as the key
	 * @param $params     The params to lookup the record, see generateWhereClause() method
	 * @param $ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @param $pagination An array of the form @code{.php}['limit' => *limit*[, 'offset' => *offset*]]@endcode
	 * @param $grouping   An array of the form @code{.php}['*column_name1*', '*column_name2*', ...]@endcode
	 * @return \b array An associative array of the models keyed on $key
	 */
	public function getMultiKeyed($key, array $params = NULL, array $ordering = NULL, array $pagination = NULL, array $grouping = NULL) {
		$table = $this->table;
		$sql   = "SELECT $table.* FROM ".$this->generateFromClause($params, $ordering);
		if ($params) {
			$where = $this->generateWhereClause($params);
			if ($where) {
				$sql .= " WHERE $where";
			}
		}
		$sql .= $this->getOrderGroupSQL($ordering, $pagination, $grouping);
		$records = $this->database->queryMulti($sql);

		$models = [];
		foreach ($records as $record) {
			$models[$record[$key]] = $this->getModel(get_class($this), $record);
		}

		return $models;
	}

	/**
	 * Generate the FROM clause for a SQL statement
	 * @param $params     The params to lookup the record, see generateWhereClause() method
	 * @param $ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @return \b string A SQL fragment
	 */
	public function generateFromClause(array $params = NULL, array $ordering = NULL, array &$in_from = NULL) {
		if (!$params) $params = [];
		if (!$ordering) $ordering = [];

		$tables = [];
		if ($in_from == NULL) {
			$tables = [ $this->table ];
			$in_from = [ $this->table => 1];
		}

		foreach (array_merge($params, $ordering) as $column => $value) {
			foreach ($this->relationships as $table => $data) {
				$column_parts = explode(':', $column);
				$column = $column_parts[0];
				$table_parts = explode(':', $table);
				$table = $table_parts[0];
				if ($table == '__common_join__') continue;

				if ($column == 'or' || $column == 'and') {
					$this_table = trim($this->generateFromClause($value, NULL, $in_from));
					if ($this_table && !isset($in_from[$this_table])) {
						$tables[] = $this_table;
						$in_from[$this_table] = 1;
					}
				}
				else if (!isset($in_from[$table]) && in_array($column, $data['where_fields'])) {
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

	/**
	 * Generate the WHERE clause for a SQL statement
	 *
	 * Supports the following operators:
	 *        Type      | Format   | Description
	 *        --------- | -------- | ----------
	 *        >         | Format 2 | column > value
	 *        >=        | Format 2 | column >= value
	 *        <         | Format 2 | column < value
	 *        <=        | Format 2 | column >= value
	 *        !=        | Format 2 | column != value
	 *        like      | Format 2 | column LIKE value
	 *        ilike     | Format 2 | column ILIKE value
	 *        likelower | Format 2 | LOWER(column) LIKE strtolower(value)
	 *        in        | Format 3 | column IN (join(',', value))
	 *        notin     | Format 3 | column NOT IN (join(',', value))
	 *        isnull    | Format 4 | column IS NULL
	 *        isnotnull | Format 4 | column IS NOT NULL
	 *        upper=    | Format 2 | UPPER(column) = strtoupper(value)
	 *        lower=    | Format 2 | LOWER(column) = strtolower(value)
	 * @param $params The params to lookup the record, is an array of the form:
	 *                    @code{.php}
	 *                    $param = [
	 *                       // Format 1 ... column = value
	 *                       '*column_name1*' => 'some value',
	 *
	 *                       // Format 2 ... non-equal operator, scalar value
	 *                       '*column_name2*'  => [
	 *                           'type' => '*type*',
	 *                           'value' => '*some value*'
	 *                       ],
	 *
	 *                       // Format 3 ... non-equal operator, array value
	 *                       '*column_name3*  => [
	 *                           'type' => '*type*',
	 *                           'value' => ['*value1*', '*value2*, ...],
	 *                       ],
	 *
	 *                       // Format 4 ... non-equal operator, no value
	 *                       '*column_name4*  => [
	 *                           'type' => '*type*',
	 *                       ],
	 *                    ];
	 *                    WHERE *type* is one of the types above
	 *                    @endcode
	 * @param $and    If TRUE the clauses should be ANDed together,
	 *                    otherwise the clauses will be ORed together.
	 * @return \b string A SQL fragment
	 */
	public function generateWhereClause(array $params, $and = TRUE) {
		$where = [];
		foreach ($params as $column => $value) {
			$parts  = explode(':', $column);
			$column = $parts[0];
			if (isset($this->columns[$this->table.'_'.$column])) {
				$column = $this->table.'.'.$this->table.'_'.$column;
			}
			elseif (isset($this->columns[$column])) {
				$column = $this->table.'.'.$column;
			}
			elseif ($column == 'or') {
				$where[] = '('.$this->generateWhereClause($value, FALSE).')';
				continue;
			}
			elseif ($column == 'and') {
				$where[] = '('.$this->generateWhereClause($value, FALSE).')';
				continue;
			}
			elseif ($column == 'SQL') {
				$where[] = $value;
				continue;
			}
			else {
				$found = FALSE;
				foreach ($this->relationships as $table => $data) {
					if ($table == '__common_join__') continue;
					$table_parts = explode(':', $table);
					$table = $table_parts[0];
					if (in_array($column, $data['where_fields'])) {
						$found = TRUE;
						$parts  = explode('.', $column);
						$column = isset($parts[1]) ? $parts[1] : $parts[0];
						$column = "$table.$column";
					}
				}

				if (!$found) continue;
			}

			// its not just an equal
			if (is_array($value)) {
				switch ($value['type']) {
					case 'like':
						$where[] = $column.' LIKE '.$this->quote($value['value']);
						break;

					case 'likelower':
						$where[] = 'LOWER('.$column.') LIKE '.$this->quote(strtolower($value['value']));
						break;

					case 'ilike':
						$where[] = $column.' ILIKE '.$this->quote($value['value']);
						break;

					case 'in':
						if (is_array($value['value']) && count($value['value']) > 0) {
							foreach ($value['value'] as &$val) {
								$val = $this->quote($val);
							}
							$where[] = $column.' IN ('.join(',', $value['value']).')';
						}
						else {
							// Do not match anything
							$where[] = "1 = 2";
						}
						break;

					case 'notin':
						if ($value['value']) {
							if ($value['value'] && is_array($value['value'])) {
								foreach ($value['value'] as &$val) {
									$val = $this->quote($val);
								}
								$where[] = $column.' NOT IN ('.join(',', $value['value']).')';
							}
						}
						break;

					case 'isnull':
						$where[] = $column.' IS NULL';
						break;

					case 'isnotnull':
						$where[] = $column.' IS NOT NULL';
						break;

					case 'upper=':
						$where[] = 'UPPER('.$column.')='.$this->quote(strtoupper($value['value']));
						break;

					case 'lower=':
						$where[] = 'LOWER('.$column.')='.$this->quote(strtolower($value['value']));
						break;

					case '>':
						$where[] = $column.'>'.$this->quote($value['value']);
						break;

					case '>=':
						$where[] = $column.'>='.$this->quote($value['value']);
						break;

					case '<':
						$where[] = $column.'<'.$this->quote($value['value']);
						break;

					case '<=':
						$where[] = $column.'<='.$this->quote($value['value']);
						break;

					case '!=':
						$where[] = $column.'!='.$this->quote($value['value']);
						break;
				}
			}
			else {
				if (is_null($value)) {
					$where[] = $column.' IS NULL';
				}
				else {
					$where[] = $column.'='.$this->quote($value);
				}
			}
		}

		if ($and) {
			return join (' AND ', $where);
		}
		else {
			return join (' OR ', $where);
		}
	}

	/**
	 * Generate SQL for the LIMIT/OFFSET, ORDER BY and GROUP BY clauses
	 * @param $ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @param $pagination An array of the form @code{.php}['limit' => *limit*[, 'offset' => *offset*]]@endcode
	 * @param $grouping   An array of the form @code{.php}['*column_name1*', '*column_name2*', ...]@endcode
	 * @return \b string An SQL fragment
	 */
	public function getOrderGroupSQL(array $ordering = NULL, array $pagination = NULL, array $grouping = NULL) {
		$sql = '';
		if ($grouping) {
			foreach ($grouping as &$column) {
				$field = $this->getColumnName($column);
			}
			$sql .= " GROUP BY ".join(', ', $grouping);
		}
		if ($ordering && count($ordering)) {
			$ordering_sql = [];
			foreach ($ordering as $column => $direction) {
				$orig_direction = $direction;
				$direction = (strtolower($direction) == 'asc') ? 'ASC' : 'DESC';
				if ($column == 'random()') {
					if ($this->database->getEngine() == 'mysql') {
						$ordering_sql[] = "RAND()";
					}
					elseif ($this->database->getEngine() == 'pgsql') {
						$ordering_sql[] = "RANDOM()";
					}
				}
				else {
					$orig_column = $column;
					$column = $this->getColumnName($column);
					if ($column) {
						$ordering_sql[] = "$column $direction";
					}
					elseif ($orig_direction == 'SQL') {
						$ordering_sql[] = $orig_column;
					}
				}
			}
			if (count($ordering_sql)) {
				$sql  .= " ORDER BY ".join(',', $ordering_sql);
			}
		}
		if ($pagination) {
			if (isset($pagination['offset'])) {
				$sql .= " OFFSET ".(int)$pagination['offset']." LIMIT ".(int)$pagination['limit'];
			}
			else {
				$sql .= " LIMIT ".(int)$pagination['limit'];
			}
		}
		return $sql;
	}

	/**
	 * Get a specific model object
	 * @param $class  The full class name of the model
	 * @param $record The record array
	 * @return \b string The model object with record array set
	 */
	public function getModel($class, array $record = NULL) {
		$model = new $class($this->config, $this->database);
		if ($record) {
			if ($this->logger->isDebugEnabled()) {
				$this->logger->debug("Creating Model: $class => ".json_encode($record));
			}
			$model->setRecord($record);
		}
		return $model;
	}

	/**
	 * Create the database
	 */
	public function createDatabase() {
		$this->database->setCreatingDatabase(TRUE);

		// replace overridden models
		$create_models = [];
		$site_models = self::$site_models;
		foreach (self::$site_models as $model) {
			// is this model overriding another one
			$model_class = $this->getModel($model);
			$overridden = $model_class->getOverrideModel();
			if ($overridden) {
				// remove this model
				foreach ($site_models as $index => $model_name) {
					if ($model == $model_name) {
						array_splice($site_models, $index, 1);
					}
				}

				// replace the overridden model
				// remove this model
				foreach ($site_models as $index => $model_name) {
					if ($overridden == $model_name) {
						array_splice($site_models, $index, 1, [$model]);
					}
				}
			}
		}

		// Create the tables
		foreach ($site_models as $model) {
			$this->logger->info("Creating table: $model");
			$model = $this->getModel($model);
			if (!$model->hasTable() || !$model->getCreateSchema()) continue;
			$model->sqlHelper()->createTable();
		}

		// Create the indexes
		foreach ($site_models as $model) {
			$this->logger->info("Creating indexes: $model");
			$model = $this->getModel($model);
			if (!$model->hasTable() || !$model->getCreateSchema()) continue;
			$model->sqlHelper()->createIndexes();
		}

		// Create the uniques
		foreach ($site_models as $model) {
			$this->logger->info("Creating uniques: $model");
			$model = $this->getModel($model);
			if (!$model->hasTable() || !$model->getCreateSchema()) continue;
			$model->sqlHelper()->createUniques();
		}

		// Create the foreign keys
		foreach ($site_models as $model) {
			$this->logger->info("Creating foreign keys: $model");
			$model = $this->getModel($model);
			if (!$model->hasTable() || !$model->getCreateSchema()) continue;
			$model->sqlHelper()->createForeignKeys();
		}

		// Create the citusdb metadata
		foreach ($site_models as $model) {
			$this->logger->info("Creating CitusDB: $model");
			$model = $this->getModel($model);
			if (!$model->hasTable() || !$model->getCreateSchema()) continue;
			$model->sqlHelper()->createCitusDB();
		}

		$this->database->setCreatingDatabase(FALSE);
	}

	/**
	 * Create the database
	 */
	public function checkDatabase() {
		$updates = [];

		// replace overridden models
		$create_models = [];
		$site_models = self::$site_models;
		foreach (self::$site_models as $model) {
			// is this model overriding another one
			$model_class = $this->getModel($model);
			$overridden = $model_class->getOverrideModel();
			if ($overridden) {
				// remove this model
				foreach ($site_models as $index => $model_name) {
					if ($model == $model_name) {
						array_splice($site_models, $index, 1);
					}
				}

				// replace the overridden model
				// remove this model
				foreach ($site_models as $index => $model_name) {
					if ($overridden == $model_name) {
						array_splice($site_models, $index, 1, [$model]);
					}
				}
			}
		}

		// Create the tables
		foreach ($site_models as $model_class) {
			$this->logger->info("Checking table: $model_class");
			$model = $this->getModel($model_class);
			if (!$model->hasTable()) continue;
			$table_updates = $model->checkTable();
			if (count($table_updates)) {
				$updates[$model_class] = $table_updates;
			}
		}

		return $updates;
	}

	/**
	 * Check the table this model is associated with
	 */
	public function checkTable() {
		if (is_null($this->table)) return;

		$updates = [];
		$schema = $this->sqlHelper()->getTableSchema();

		if (!$schema) {
			return ['add_table' => TRUE];
		}

		// look for new/changed columns
		foreach ($this->columns as $name => $data) {
			if (!isset($data['auto_increment'])) {
				$data['auto_increment'] = FALSE;
			}
			if (!isset($data['null_allowed'])) {
				$data['null_allowed'] = FALSE;
			}

			// new column
			if (!isset($schema['columns'][$name])) {
				$updates['add_column'][$name] = $data;
			}
			// check for data type change
			elseif ($data['data_type'] != $schema['columns'][$name]['data_type'] ||
				$data['auto_increment'] != $schema['columns'][$name]['auto_increment'] ||
				$data['null_allowed'] != $schema['columns'][$name]['null_allowed']
			) {
				$updates['alter_column'][$name] = $data;
			}
		}
		// look for deleted columns
		foreach ($schema['columns'] as $name => $data) {
			// remove column
			if (!isset($this->columns[$name])) {
				$updates['drop_column'][] = $name;
			}
		}

		// look for new/changed indexes
		foreach ($this->indexes as $name => $columns) {
			// look over all the indexes looking for this one
			$found = FALSE;
			foreach ($schema['indexes'] as $curr_name => $curr_columns) {
				if (!is_array($columns)) {
					$columns = [$columns];
				}

				sort($curr_columns);
				sort($columns);
				$diff1 = array_diff($columns, $curr_columns);
				$diff2 = array_diff($curr_columns, $columns);
				if (count(array_merge($diff1, $diff2)) == 0) {
					$found = TRUE;
					break;
				}
			}

			// new index
			if (!$found) {
				$updates['add_index'][$name] = $columns;
			}
		}
		// look for deleted indexes
		foreach ($schema['indexes'] as $name => $columns) {
			// skip primary key
			if ($name == $schema['primary_key']['name']) {
				continue;
			}

			// skip if this is a partial index
			if (preg_match('/_part_uni$/', $name)) {
				continue;
			}

			// look over all the indexes looking for this one
			$found = FALSE;
			foreach ($this->indexes as $curr_name => $curr_columns) {
				if (!is_array($curr_columns)) {
					$curr_columns = [$curr_columns];
				}

				sort($curr_columns);
				sort($columns);
				$diff1 = array_diff($columns, $curr_columns);
				$diff2 = array_diff($curr_columns, $columns);
				if (count(array_merge($diff1, $diff2)) == 0) {
					$found = TRUE;
					break;
				}
			}

			// remove index
			if (!$found) {
				$updates['drop_index'][] = $name;
			}
		}

		// look for new/changed uniques
		foreach ($this->uniques as $name => $columns) {
			// look over all the uniques looking for this one
			$found = FALSE;
			foreach ($schema['uniques'] as $curr_name => $curr_columns) {
				if (!is_array($columns)) {
					$columns = [$columns];
				}

				sort($curr_columns);
				sort($columns);
				$diff1 = array_diff($columns, $curr_columns);
				$diff2 = array_diff($curr_columns, $columns);
				if (count(array_merge($diff1, $diff2)) == 0) {
					$found = TRUE;
					break;
				}
			}

			// new unique
			if (!$found) {
				$updates['add_unique'][$name] = $columns;
			}
		}
		// look for deleted uniques
		foreach ($schema['uniques'] as $name => $columns) {
			// skip primary key
			if ($name == $schema['primary_key']['name']) {
				continue;
			}

			// look over all the uniques looking for this one
			$found = FALSE;
			foreach ($this->uniques as $curr_name => $curr_columns) {
				if (!is_array($curr_columns)) {
					$curr_columns = [$curr_columns];
				}

				sort($curr_columns);
				sort($columns);
				$diff1 = array_diff($columns, $curr_columns);
				$diff2 = array_diff($curr_columns, $columns);
				if (count(array_merge($diff1, $diff2)) == 0) {
					$found = TRUE;
					break;
				}
			}

			// remove unique
			if (!$found) {
				$updates['drop_unique'][] = $name;
			}
		}

		// look for new/changed parital uniques
		foreach ($this->partial_uniques as $name => $columns) {
			// look over all the uniques looking for this one
			$orig_columns = $columns;
			$index_columns = $columns;
			array_shift($index_columns);
			$part_uni_name = $this->sqlHelper()->indexConstraintName($index_columns).'_part_uni';
			$found = FALSE;
			foreach ($schema['indexes'] as $curr_name => $curr_columns) {
				if (!is_array($columns)) {
					$columns = [$columns];
				}

				sort($curr_columns);
				sort($columns);
				$diff1 = array_diff($columns, $curr_columns);
				$diff2 = array_diff($curr_columns, $columns);
				if (count(array_merge($diff1, $diff2)) == 0 || $part_uni_name == $curr_name) {
					$found = TRUE;
					break;
				}
			}

			// new unique
			if (!$found) {
				$updates['add_partial_unique'][$name] = $orig_columns;
			}
		}

		// look for new/changed foreign_keys
		foreach ($this->foreign_keys as $name => $data) {
			// new foreign_key
			if (!isset($schema['foreign_keys'][$name])) {
				$updates['add_foreign_key'][$name] = $data;
			}
		}
		// look for deleted foreign_keys
		foreach ($schema['foreign_keys'] as $name => $data) {
			// remove foreign_key
			if (!isset($this->foreign_keys[$name])) {
				$updates['drop_foreign_key'][] = $name;
			}
		}

		return $updates;
	}

	/**
	 * Insert the inital data in the table this model is associated with
	 */
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

	public function updateDatabase(array $updates) {
		foreach ($updates as $class => $updates) {
			$model = $this->getModel($class);

			// apply the updates
			foreach ($updates as $type => $update) {
				switch ($type) {
					// create the table
					case 'add_table':
						$model->sqlHelper()->createTable();
						break;

					// add a column
					case 'add_column':
						foreach ($update as $name => $data) {
							$model->sqlHelper()->addColumn($name, $data);
						}
						break;

					// drop a column
					case 'drop_column':
						foreach ($update as $name) {
							$model->sqlHelper()->dropColumn($name);
						}
						break;

					// alter a column
					case 'alter_column':
						foreach ($update as $name => $data) {
							$model->sqlHelper()->alterColumn($name, $data);
						}
						break;

					// add an index
					case 'add_index':
						foreach ($update as $columns) {
							$model->sqlHelper()->addIndex($columns);
						}
						break;

					// drop an index
					case 'drop_index':
						foreach ($update as $columns) {
							$model->sqlHelper()->dropIndex($columns);
						}
						break;

					// add an unique
					case 'add_unique':
						foreach ($update as $columns) {
							$model->sqlHelper()->addUnique($columns);
						}
						break;

					// add a partial unique
					case 'add_partial_unique':
						foreach ($update as $columns) {
							$condition = array_shift($columns);
							$model->sqlHelper()->addPartialUnique($condition, $columns);
						}
						break;

					//  an unique
					case 'drop_unique':
						foreach ($update as $name) {
							$model->sqlHelper()->dropUnique($name);
						}
						break;

					// add a foreign key
					case 'add_foreign_key':
						foreach ($update as $column => $data) {
							$model->sqlHelper()->addForeignKey($column, $data[0], $data[1]);
						}
						break;

					// drop a foreign key
					case 'drop_foreign_key':
						foreach ($update as $column => $data) {
							$model->sqlHelper()->dropForeignKey($column);
						}
						break;
				}
			}
		}
	}
}
