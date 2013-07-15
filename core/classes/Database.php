<?php

namespace core\classes;

use PDO;
use core\classes\exceptions\DatabaseException;

class Database extends PDO {
	//connection properties
	private $engine;
	private $hostname;
	private $username;
	private $database;
	private $password;
	private $persistant;

	private $logger;

	/**
	 * Constructor
	 */
	public function __construct($engine, $hostname, $username, $database, $password, $persistant = FALSE) {
		$this->engine   = $engine;
		$this->hostname = $hostname;
		$this->username = $username;
		$this->database = $database;
		$this->password = $password;
		$this->persistant = $persistant;

		$this->logger = Logger::getLogger(__CLASS__);

		$options = [];
		if ($this->persistant) {
			$options = [PDO::ATTR_PERSISTENT => true];
		}

        $dns = $this->engine.':dbname='.$this->database.";host=".$this->hostname;
		parent::__construct($dns, $this->username, $this->password, $options);
	}

	public function quote($string, $paramtype = NULL) {
		return $this->quote($string, $paramtype);
	}

	/**
	 *
	 */
	public function executeQuery($sql) {
		$statement = $this->query($sql);
		if (!$statement) {
			$message = "SQL ERROR: {$this->errorCode()} ".join("\n", $this->errorInfo())."\nSQL: $sql";
			throw new DatabaseException($message);
		}
		return $statement;
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the first field from the first row from the result set. Used for SELECT queries only.
	 */
	public function queryValue($sql) {
		$statement = $this->executeQuery($sql);
		$returnArray = $statement->fetch(PDO::FETCH_NUM);
		return $returnArray[0];
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the first row from the result set. Used for SELECT queries only.
	 */
	public function querySingle($sql) {
		$statement = $this->executeQuery($sql);
		return $statement->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the result set. Used for SELECT queries only.
	 */
	public function queryMulti($sql) {
		$statement = $this->executeQuery($sql);
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		if (!$result) return [];
		return $result;
	}

	/**
	 * A specialised query function that executes the query and then returns
	 * the result set. Used for SELECT queries only.
	 */
	public function queryMultiKeyed($sql, $field) {
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
	 *
	 */
	public function queryList($sql) {
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
	 *
	 */
	public function queryKeyValue($sql) {
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
