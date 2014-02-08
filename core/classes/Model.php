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
	 * An array containing table columns with data types.<br>
	 * Valid data types: smallint, int, bigint, numeric, date, datetime, bool, text, blob <br>
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
	protected $site_models;

	/**
	 * Constructor
	 * @param[in] config   The configuration object
	 * @param[in] database The database object
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
	}

	/**
	 * Finds all the available models for this site and stores them in $this->site_models
	 * @param[in] site  [Optional] Get models for a sepecific site, if NULL then
	 *                  this will find all models for all sites.
	 */
	protected function findAllModels($site = NULL) {
		if (isset($GLOBALS['cache']['site_models'])) {
			$this->site_models = $GLOBALS['cache']['site_models'];
			return;
		}

		$sites = $this->config->sites;
		if ($site) {
			$sites = [ $site ];
		}

		$site_models = [];
		foreach ($sites as $site) {
			$config = clone $this->config;
			$config->setSiteDomain($site->domain, FALSE);

			$site_controllers = [];
			$core_controllers = [];
			$root_path = __DIR__.DS.'..'.DS.'..'.DS;

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
					if (preg_match('|^'.$root_path.'(.*?)'.DS.'([\w]+)\.php$|', $filename, $matches)) {
						$class = str_replace('/', '\\', $matches[1]).'\\'.$matches[2];
						$site_models[$class] = 1;
					}
				}
			}
		}

		$this->site_models = [];
		foreach ($site_models as $class => $value) {
			$reflectionClass = new ReflectionClass($class);
			if ($reflectionClass->IsInstantiable()) {
				$this->site_models[] = $class;
			}
		}

		$GLOBALS['cache']['site_models'] = $this->site_models;
	}

	/**
	 * Get the record array
	 * @return The record array
	 */
	public function getRecord() {
 		return $this->record;
	}

	/**
	 * Get the available models
	 * @return An array containing the available models
	 */
	public function getSiteModels() {
 		return $this->site_models;
	}

	/**
	 * Set the record array
	 * @param[in] record The array to set as the record array
	 */
	public function setRecord(array $record) {
		$this->record = $record;
	}

	/**
	 * Set an element in the objects array
	 * @param[in] key    The element's key
	 * @param[in] object The object
	 */
	public function setObjectCache($key, $object) {
		$this->objects[$key] = $object;
	}

	/**
	 * Sets an element in the record array
	 * @param[in] name  The column name
	 * @param[in] value The value for the column
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
	 * @param[in] name The column name
	 * @return The value of the column
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
	 * @param[in] name The column name
	 * @return The database column name or NULL if it could not be translated
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
	 * @param[in] name The name of the hook
	 * @return The return value of the hook
	 */
	protected function callHook($name) {
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
	 * @return The primary key's value
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
		$this->logger->debug("Inserted record in $table => ".$this->record[$primary_key]);

		$this->callHook('insert');

		return $this->record[$primary_key];
	}

	/**
	 * Update this model in the database
	 */
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

	/**
	 * Delete this model from the database
	 */
	public function delete() {
		$table       = $this->table;
		$primary_key = $this->primary_key;

		if (!isset($this->record[$primary_key])) {
			throw new ModelException("Object has no primary key: ".print_r($this, TRUE));
		}

		$this->callHook('delete');

		$sql = "DELETE FROM $table WHERE $primary_key = ".$this->database->quote($this->record[$primary_key]);
		$this->database->executeQuery($sql);
	}

	/**
	 * Get a model from the database.  The first record is used to create the model.
	 * @param[in] params   The params to lookup the record, see generateWhereClause()
	 * @param[in] ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @return The model object
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
	 * @param[in] params The params to lookup the record, see generateWhereClause() method
	 * @return The number of records found
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
	 * @param[in] params The params to lookup the record, see generateWhereClause() method
	 * @param[in] ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @param[in] pagination An array of the form @code{.php}['limit' => *limit*[, 'offset' => *offset*]]@endcode
	 * @param[in] grouping   An array of the form @code{.php}['*column_name1*', '*column_name2*', ...]@endcode
	 * @return An array of model objects
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
	 * @param[in] key        The column name of the column to use as the key
	 * @param[in] params     The params to lookup the record, see generateWhereClause() method
	 * @param[in] ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @param[in] pagination An array of the form @code{.php}['limit' => *limit*[, 'offset' => *offset*]]@endcode
	 * @param[in] grouping   An array of the form @code{.php}['*column_name1*', '*column_name2*', ...]@endcode
	 * @return An associative array of the models keyed on $key
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
	 * @param[in] params     The params to lookup the record, see generateWhereClause() method
	 * @param[in] ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @return A SQL fragment
	 */
	protected function generateFromClause(array $params = NULL, array $ordering = NULL) {
		if (!$params) $params = [];
		if (!$ordering) $ordering = [];
		$tables = [ $this->table ];
		$in_from = [];
		foreach (array_merge($params, $ordering) as $column => $value) {
			foreach ($this->relationships as $table => $data) {
				$table_parts = explode(':', $table);
				$table = $table_parts[0];
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
	 * @param[in] params The params to lookup the record, is an array of the form:
	 *                   @code{.php}
	 *                   $param = [
	 *                      // Format 1 ... column = value
	 *                      '*column_name1*' => 'some value',
	 *
	 *                      // Format 2 ... non-equal operator, scalar value
	 *                      '*column_name2*'  => [
	 *                          'type' => '*type*',
	 *                          'value' => '*some value*'
	 *                      ],
	 *
	 *                      // Format 3 ... non-equal operator, array value
	 *                      '*column_name3*  => [
	 *                          'type' => '*type*',
	 *                          'value' => ['*value1*', '*value2*, ...],
	 *                      ],
	 *
	 *                      // Format 4 ... non-equal operator, no value
	 *                      '*column_name4*  => [
	 *                          'type' => '*type*',
	 *                      ],
	 *                   ];
	 *                   WHERE *type* is one of the types above
	 *                   @endcode
	 * @param[in] and    If TRUE the clauses should be ANDed together,
	 *                   otherwise the clauses will be ORed together.
	 * @return A SQL fragment
	 */
	protected function generateWhereClause(array $params, $and = TRUE) {
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
						$where[] = $column.' LIKE '.$this->database->quote($value['value']);
						break;

					case 'likelower':
						$where[] = 'LOWER('.$column.') LIKE '.$this->database->quote(strtolower($value['value']));
						break;

					case 'ilike':
						$where[] = $column.' ILIKE '.$this->database->quote($value['value']);
						break;

					case 'in':
						if ($value['value']) {
							foreach ($value['value'] as &$val) {
								$val = $this->database->quote($val);
							}
							$where[] = $column.' IN ('.join(',', $value['value']).')';
						}
						break;

					case 'notin':
						if ($value['value']) {
							foreach ($value['value'] as &$val) {
								$val = $this->database->quote($val);
							}
							$where[] = $column.' NOT IN ('.join(',', $value['value']).')';
						}
						break;

					case 'isnull':
						$where[] = $column.' IS NULL';
						break;

					case 'isnotnull':
						$where[] = $column.' IS NOT NULL';
						break;

					case 'upper=':
						$where[] = 'UPPER('.$column.')='.$this->database->quote(strtoupper($value['value']));
						break;

					case 'lower=':
						$where[] = 'LOWER('.$column.')='.$this->database->quote(strtolower($value['value']));
						break;

					case '>':
						$where[] = $column.'>'.$this->database->quote($value['value']);
						break;

					case '>=':
						$where[] = $column.'>='.$this->database->quote($value['value']);
						break;

					case '<':
						$where[] = $column.'<'.$this->database->quote($value['value']);
						break;

					case '<=':
						$where[] = $column.'<='.$this->database->quote($value['value']);
						break;

					case '!=':
						$where[] = $column.'!='.$this->database->quote($value['value']);
						break;
				}
			}
			else {
				if (is_null($value)) {
					$where[] = $column.' IS NULL';
				}
				else {
					$where[] = $column.'='.$this->database->quote($value);
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
	 * @param[in] ordering   An array of the form @code{.php}['*column_name*' => '*asc|desc*', ...]@endcode
	 * @param[in] pagination An array of the form @code{.php}['limit' => *limit*[, 'offset' => *offset*]]@endcode
	 * @param[in] grouping   An array of the form @code{.php}['*column_name1*', '*column_name2*', ...]@endcode
	 * @return An SQL fragment
	 */
	protected function getOrderGroupSQL(array $ordering = NULL, array $pagination = NULL, array $grouping = NULL) {
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
					$column = $this->getColumnName($column);
					if ($column) {
						$ordering_sql[] = "$column $direction";
					}
				}
			}
			if (count($ordering_sql)) {
				$sql  .= " ORDER BY ".join(',', $ordering_sql);
			}
		}
		if ($pagination) {
			if (!isset($pagination['offset'])) $pagination['offset'] = 0;
			$sql .= " OFFSET ".(int)$pagination['offset']." LIMIT ".(int)$pagination['limit'];
		}
		return $sql;
	}

	/**
	 * Get a specific model object
	 * @param[in] class  The full class name of the model
	 * @param[in] record The record array
	 * @return The model object with record array set
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
		// Create the tables
		foreach ($this->site_models as $model) {
			$this->logger->info("Creating table: $model");
			$model = $this->getModel($model);
			$model->createTable();
		}

		// Create the indexes
		foreach ($this->site_models as $model) {
			$this->logger->info("Creating indexes: $model");
			$model = $this->getModel($model);
			$model->createIndexes();
		}

		// Create the foreign keys
		foreach ($this->site_models as $model) {
			$this->logger->info("Creating foreign keys: $model");
			$model = $this->getModel($model);
			$model->createForeignKeys();
		}
	}

	/**
	 * Drop the table this model is associated with
	 */
	public function dropTable() {
		if (is_null($this->table)) return;

		$sql = 'DROP TABLE '.$this->table;
		return $this->database->executeQuery($sql);
	}

	/**
	 * Create the table this model is associated with
	 */
	public function createTable() {
		if (is_null($this->table)) return;

		if ($this->database->getEngine() == 'mysql') {
			return $this->createTableMySQL();
		}
		elseif ($this->database->getEngine() == 'pgsql') {
			return $this->createTablePgSQL();
		}
	}

	/**
	 * Create the foreign keys for the table this model is associated with
	 */
	public function createForeignKeys() {
		if (is_null($this->table)) return;

		if ($this->database->getEngine() == 'mysql') {
			return $this->createForeignKeysMySQL();
		}
		elseif ($this->database->getEngine() == 'pgsql') {
			return $this->createForeignKeysPgSQL();
		}
	}

	/**
	 * Create the indexes for the table this model is associated with
	 */
	public function createIndexes() {
		if (is_null($this->table)) return;

		if ($this->database->getEngine() == 'mysql') {
			return TRUE;
		}
		elseif ($this->database->getEngine() == 'pgsql') {
			return $this->createIndexesPgSQL();
		}
	}

	/**
	 * Translate an element of the $columns array to a data type clause
	 * @param data An element from $this->columns
	 * @return An SQL fragment
	 */
	protected function getDataType($data) {
		if ($this->database->getEngine() == 'mysql') {
			return $this->getDataTypeMySQL($data);
		}
		elseif ($this->database->getEngine() == 'pgsql') {
			return $this->getDataTypePgSQL($data);
		}
	}

	/**
	 * Create the MySQL table this model is associated with
	 */
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

	/**
	 * Create the MySQL table foreign keys this model is associated with
	 */
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

	/**
	 * Translate an element of the $columns array to a MySQL data type clause
	 * @param data An element from $this->columns
	 * @return An SQL fragment
	 */
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

	/**
	 * Create the PostgreSQL table this model is associated with
	 */
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

	/**
	 * Create the PostgreSQL table foreign keys this model is associated with
	 */
	protected function createForeignKeysPgSQL() {
		// add the forign keys
		foreach ($this->foreign_keys as $column => $foreign) {
			$foreign_table  = $foreign[0];
			$foreign_column = $foreign[1];
			$sql = "ALTER TABLE ONLY ".$this->table." ADD CONSTRAINT ".$this->table."_".$column."_fk FOREIGN KEY ($column) REFERENCES $foreign_table ($foreign_column);";

			$this->database->executeQuery($sql);
		}
	}

	/**
	 * Create the PostgreSQL table indexes this model is associated with
	 */
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

	/**
	 * Translate an element of the $columns array to a PostgreSQL data type clause
	 * @param data An element from $this->columns
	 * @return An SQL fragment
	 */
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
				$type = 'TIMESTAMP WITH time zone';
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
}
