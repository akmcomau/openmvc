<?php

namespace core\classes;

use PDO;
use core\classes\exceptions\DatabaseException;

/**
 * The MySQL and PostgreSQL database driver
 */
class Database extends PDO {
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

	/**
	 * Constructor
	 * @param[in] $engine     \b string The database engine to use, either: mysql, pgsql, none
	 * @param[in] $hostname   \b string The database server's hostname
	 * @param[in] $username   \b string The database user name
	 * @param[in] $database   \b string The database name
	 * @param[in] $password   \b string The database password
	 * @param[in] $persistant \b boolean Should this be a persistant connection
	 */
	public function __construct($engine, $hostname, $username, $database, $password, $persistant = FALSE) {
		$this->engine     = $engine;
		$this->hostname   = $hostname;
		$this->username   = $username;
		$this->database   = $database;
		$this->password   = $password;
		$this->persistant = $persistant;

		if ($this->engine != 'mysql' && $this->engine != 'pgsql' && $this->engine != 'none') {
			throw new DatabaseException('Invalid database engine: '.$this->engine);
		}

		$this->logger = Logger::getLogger(__CLASS__);

		$options = [];
		if ($this->persistant) {
			$options = [PDO::ATTR_PERSISTENT => true];
		}

		if ($this->engine == 'none') {
			return;
		}

        $dns = $this->engine.':dbname='.$this->database.";host=".$this->hostname;
		parent::__construct($dns, $this->username, $this->password, $options);
	}

	/**
	 * Gets the current database engine
	 * @return \string The database engine, either mysql, pgsql or none
	 */
	public function getEngine() {
		return $this->engine;
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
	 * @param[in] $key   \b string The key of the cached value
	 * @param[in]$value  \b mixed  The value to store
	 */
	public function setCache($key, $value) {
		if ($this->cache_enabled) {
			$this->cache[$key] = $value;
		}
	}

	/**
	 * Get an element in the objects array
	 * @param[in] $key   \b string The key of the cached value
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
	 * @param[in] $value          \b mixed The value to quote
	 * @param[in] $parameter_type \b string The parameter type
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
	 * @param[in] $sql  \b string The SQL statement
	 * @return PDOStatement The result of executing the SQL statement
	 * @throws DatabaseException If an error was returned
	 */
	public function executeQuery($sql) {
		if ($this->engine == 'none') {
			return NULL;
		}

		$this->logger->debug("Executing SQL: $sql");
		$statement = $this->query($sql);
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
	 * @param[in] $sql  \b string The SQL statement
	 * @return mixed The value from the database
	 */
	public function queryValue($sql) {
		if ($this->engine == 'none') {
			return NULL;
		}

		$statement = $this->executeQuery($sql);
		$returnArray = $statement->fetch(PDO::FETCH_NUM);
		return $returnArray[0];
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the first row from the result set. Used for SELECT queries only.
	 * @param[in] $sql  \b string The SQL statement
	 * @return array A single record
	 */
	public function querySingle($sql) {
		if ($this->engine == 'none') {
			return NULL;
		}

		$statement = $this->executeQuery($sql);
		return $statement->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the result set. Used for SELECT queries only.
	 * @param[in] $sql  \b string The SQL statement
	 * @return array An array of records
	 */
	public function queryMulti($sql) {
		if ($this->engine == 'none') {
			return [];
		}

		$statement = $this->executeQuery($sql);
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		if (!$result) return [];
		return $result;
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the result set, keyed on a particular field. Used for SELECT queries only.
	 * @param[in] $sql    \b string The SQL statement
	 * @param[in] $field  \b string Column to use as the key for the result array
	 * @return array An assoc array of records
	 */
	public function queryMultiKeyed($sql, $field) {
		if ($this->engine == 'none') {
			return [];
		}

		$this->executeQuery($sql);
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
	 * @param[in] $sql  \b string The SQL statement
	 * @return array An list of values
	 */
	public function queryList($sql) {
		if ($this->engine == 'none') {
			return [];
		}

		$this->executeQuery($sql);
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
	 * @param[in] $sql  \b string The SQL statement
	 * @return array An list of values
	 */
	public function queryKeyValue($sql) {
		if ($this->engine == 'none') {
			return [];
		}

		$this->executeQuery($sql);
		$result = $statement->fetchAll(PDO::FETCH_NUM);
		if (!$result) return [];

		$keyed = [];
		foreach ($result as $record) {
			$keyed[$record[0]] = $record[1];
		}
		return $keyed;
	}
}
