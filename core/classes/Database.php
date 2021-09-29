<?php

namespace core\classes;

use PDO;
use core\classes\exceptions\DatabaseException;

/**
 * The MySQL and PostgreSQL database driver
 */
class Database extends PDO {
	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The database engine to use, either: mysql, pgsql, none
	 * @var string $engine
	 */
	protected $engine;

	/**
	 * The database server's hostname
	 * @var string $hostname
	 */
	protected $hostname;

	/**
	 * The database user name
	 * @var string $username
	 */
	protected $username;

	/**
	 * The database name
	 * @var string $database
	 */
	protected $database;

	/**
	 * The database password
	 * @var string $password
	 */
	protected $password;

	/**
	 * The database port
	 * @var string $port
	 */
	protected $port;

	/**
	 * Should this be a persistant connection
	 * @var string $persistant
	 */
	protected $persistant;

	/**
	 * The Logger object
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * Is this a connection to the msater database
	 * @var boolean $is_masterdb Is this the Master DB
	 */
	protected $is_masterdb = FALSE;

	/**
	 * Is this a connection to the msater database
	 * @var Database $masterdb The connection to the Master DB
	 */
	protected $masterdb = NULL;

	/**
	 * Is caching enabled
	 * @var boolean $cache_enabled
	 */
	protected $cache_enabled = TRUE;

	/**
	 * The cache storage area
	 * @var array $cache
	 */
	protected $cache = [];

	/**
	 * Is the database currently being created
	 * @var boolean $creating_database
	 */
	protected $creating_database = FALSE;

	static public $db_time = 0;

	/**
	 * Constructor
	 * @param $config     \b Config The configuration object
	 */
	public function __construct($config, $force_masterdb = FALSE) {
		// set the defaults (master if this is a master/slave setup)
		$this->config     = $config;
		$this->logger     = Logger::getLogger(__CLASS__);
		$this->engine     = $config->database->engine;
		$this->hostname   = $config->database->hostname;
		$this->port       = property_exists($config->database, 'port') ? $config->database->port : NULL;
		$this->username   = $config->database->username;
		$this->database   = $config->database->database;
		$this->password   = $config->database->password;
		$this->persistant = property_exists($config->database, 'persistant') ? $config->database->persistant : NULL;

		if ($this->engine != 'mysql' && $this->engine != 'pgsql' && $this->engine != 'none') {
			throw new DatabaseException('Invalid database engine: '.$this->engine);
		}

		// Make persistent if needed
		$options = [];
		if ($this->persistant) {
			$options = [PDO::ATTR_PERSISTENT => true];
		}

		// if there is no database for this site
		if ($this->engine == 'none') {
			return;
		}

		// check if we should be connecting to a slave instead
		if (
			property_exists($this->config->database, 'slavedb') &&
			$this->config->database->slavedb &&
			!$this->config->database->slavedb_config->only_master &&
			!$force_masterdb
		) {
			// create the list of slaves
			$max_index = 0;
			$slaves = [];
			if ($this->config->database->slavedb_config->master_for_reads) {
				$end = $max_index + $this->config->database->slavedb_config->master_probability;
				$slaves[] = [
					'start'  => $max_index,
					'end'    => $end,
					'config' => ['is_masterdb' => TRUE],
				];
				$max_index = $end;
			}
			foreach ($this->config->database->slavedb_config->slaves as $slave) {
				$end = $max_index + $slave->probability;
				$slaves[] = [
					'start'  => $max_index,
					'end'    => $end,
					'config' => $slave,
				];
				$max_index = $end;
			}

			// randomly pick a database
			$random_slave = NULL;
			$random = $max_index * rand() / getrandmax();
			foreach ($slaves as $slave) {
				if ($random > $slave['start'] && $random < $slave['end']) {
					$random_slave = $slave['config'];
					break;
				}
			}

			// if a slave was picked, override the settings
			if ($random_slave) {
				foreach ((array)$random_slave as $key => $value) {
					$this->$key = $value;
				}
			}
		}
		else {
			// we did not select a slave so this must be the mster
			$this->is_masterdb = TRUE;
		}

		// create DSN and call parent constructor
		$dsn = $this->engine.':dbname='.$this->database.";host=".$this->hostname;
		if ($this->port) $dsn .= ";port=".$this->port;
		$this->logger->debug("DB Connection String: $dsn");
		parent::__construct($dsn, $this->username, $this->password, $options);

		// if citusdb is enabled then set the replication factor and num shards
		if (property_exists($this->config->database, 'citusdb') && $this->config->database->citusdb) {
			$sql = "SET citus.shard_replication_factor = ".(int)$this->config->database->citusdb_replicas;
			$this->executeQuery($sql);

			$sql = "SET citus.shard_max_size = ".$this->quote($this->config->database->citusdb_shard_size);
			$this->executeQuery($sql);
		}
	}

	/**
	 * Gets the current database engine
	 * @return \string The database engine, either mysql, pgsql or none
	 */
	public function getEngine() {
		return $this->engine;
	}

	/**
	 * Gets the Master DB connection
	 * @return \Database A connection to the master database
	 */
	public function getMasterDB() {
		// if this is the master db, just return $this
		if ($this->is_masterdb) {
			return $this;
		}

		// if there is no connection to the master database yet, its time to connect
		if (!$this->masterdb) {
			$this->masterdb = new Database($this->config, TRUE);
		}

		return $this->masterdb;
	}

	/**
	 * Sets creating database field
	 */
	public function setCreatingDatabase($creating_database) {
		$this->creating_database = $creating_database ? TRUE : FALSE;
	}

	/**
	 * Fetches creating database field
	 */
	public function getCreatingDatabase() {
		return $this->creating_database;
	}

	/**
	 * Enables caching
	 */
	public function enableCache() {
		$this->cache_enabled = TRUE;
	}

	/**
	 * Disables caching
	 */
	public function disableCache() {
		$this->cache_enabled = FALSE;
	}

	/**
	 * Set an element in the objects array
	 * @param $key   \b string The key of the cached value
	 * @param$value  \b mixed  The value to store
	 */
	public function setCache($key, $value) {
		if ($this->cache_enabled) {
			$this->cache[$key] = $value;
		}
	}

	/**
	 * Get an element in the objects array
	 * @param $key   \b string The key of the cached value
	 * @return \b mixed The cached value or NULL of it is not in the cache store
	 */
	public function getCache($key) {
		if ($this->cache_enabled && isset($this->cache[$key])) {
			return $this->cache[$key];
		}
		return NULL;
	}

	/**
	 * Quote a variable for use in an SQL statement
	 * @param $value          \b mixed The value to quote
	 * @param $parameter_type \b string The parameter type
	 */
	public function quote($value, $parameter_type = NULL) {
		if ($this->engine == 'none') {
			return '';
		}

		if (is_bool($value)) {
			return $value ? 'TRUE' : 'FALSE';
		}
		elseif (is_integer($value)) {
			return (int)$value;
		}
		elseif (is_null($value)) {
			return 'NULL';
		}
		return parent::quote($value, $parameter_type);
	}

	/**
	 * Execute an SQL statement
	 * @param $sql  \b string The SQL statement
	 * @return PDOStatement The result of executing the SQL statement
	 * @throws DatabaseException If an error was returned
	 */
	public function executeQuery($sql, $use_masterdb = FALSE) {
		if ($this->engine == 'none') {
			return NULL;
		}

		$this->logger->debug("Executing SQL: $sql");
		$start = microtime(TRUE);
		$statement = $this->query($sql);
		$exec_time = microtime(TRUE) - $start;
		Database::$db_time += $exec_time;
		$this->logger->debug("SQL Time: $exec_time");
		if (!$statement) {
			$message = "SQL ERROR: {$this->errorCode()} ".join("\n", $this->errorInfo())."\nSQL: $sql";
			$this->logger->error($message);
			throw new DatabaseException($message);
		}
		return $statement;
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the first field from the first row from the result set. Used for SELECT queries only.
	 * @param $sql  \b string The SQL statement
	 * @return mixed The value from the database
	 */
	public function queryValue($sql, $use_masterdb = FALSE) {
		if ($this->engine == 'none') {
			return NULL;
		}

		$statement = $this->executeQuery($sql, $use_masterdb);
		$returnArray = $statement->fetch(PDO::FETCH_NUM);
		if (is_array($returnArray)) {
			return $returnArray[0];
		}
		return NULL;
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the first row from the result set. Used for SELECT queries only.
	 * @param $sql  \b string The SQL statement
	 * @return array A single record
	 */
	public function querySingle($sql, $use_masterdb = FALSE) {
		if ($this->engine == 'none') {
			return NULL;
		}

		$statement = $this->executeQuery($sql, $use_masterdb);
		return $statement->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the result set. Used for SELECT queries only.
	 * @param $sql  \b string The SQL statement
	 * @return array An array of records
	 */
	public function queryMulti($sql, $use_masterdb = FALSE) {
		if ($this->engine == 'none') {
			return [];
		}

		$statement = $this->executeQuery($sql, $use_masterdb);
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		if (!$result) return [];
		return $result;
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the result set, keyed on a particular field. Used for SELECT queries only.
	 * @param $sql    \b string The SQL statement
	 * @param $field  \b string Column to use as the key for the result array
	 * @return array An assoc array of records
	 */
	public function queryMultiKeyed($sql, $field, $use_masterdb = FALSE) {
		if ($this->engine == 'none') {
			return [];
		}

		$statement = $this->executeQuery($sql, $use_masterdb);
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		if (!$result) return [];

		$keyed = [];
		foreach ($result as $record) {
			$keyed[$record[$field]] = $record;
		}
		return $keyed;
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the first column of each record. Used for SELECT queries only.
	 * @param $sql  \b string The SQL statement
	 * @return array An list of values
	 */
	public function queryList($sql, $use_masterdb = FALSE) {
		if ($this->engine == 'none') {
			return [];
		}

		$statement = NULL;
		if ($use_masterdb) {
			$statement = $this->masterdb->executeQuery($sql, $use_masterdb);
		}
		else {
			$statement = $this->executeQuery($sql, $use_masterdb);
		}
		$result = $statement->fetchAll(PDO::FETCH_NUM);
		if (!$result) return [];

		$list = [];
		foreach ($result as $record) {
			$list[] = $record[0];
		}
		return $list;
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the an assoc array, using the first column as the key and the second
	 * for the value. Used for SELECT queries only.
	 * @param $sql  \b string The SQL statement
	 * @return array An list of values
	 */
	public function queryKeyValue($sql, $use_masterdb = FALSE) {
		if ($this->engine == 'none') {
			return [];
		}

		$statement = $this->executeQuery($sql, $use_masterdb);
		$result = $statement->fetchAll(PDO::FETCH_NUM);
		if (!$result) return [];

		$keyed = [];
		foreach ($result as $record) {
			$keyed[$record[0]] = $record[1];
		}
		return $keyed;
	}
}
