<?php

namespace core\classes;

use ReflectionClass;
use core\classes\exceptions\AutoloaderException;
use core\classes\exceptions\ModelException;

class DatabaseDriver {

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
	 * The model object this driver object is for
	 * @var Model $model
	 */
	protected $model;

	/**
	 * The column name of the primary key for the table
	 * @var string $primary_key
	 */
	protected $primary_key = NULL;

	/**
	 * An array containing table columns with data types.
	 * @var array $columns
	 */
	protected $columns = NULL;

	/**
	 * An array containing the indexes for the table.
	 * @var array $indexes
	 */
	protected $indexes = [];

	/**
	 * An array containing foreign keys for the table.
	 * @var array $foreign_keys
	 */
	protected $foreign_keys = [];

	/**
	 * An array containing the unique constraints for the table
	 * @var array $uniques
	 */
	protected $uniques = [];

	/**
	 * An array containing the partial unique constraints for the table
	 * @var array $uniques
	 */
	protected $partial_uniques = [];

	/**
	 * Extra data for CitusDB
	 * @var array $objects
	 */
	protected $citusdb = NULL;

	/**
	 * Constructor
	 * @param $config   \b Config   The configuration object
	 * @param $database \b Database The database object
	 * @param $model    \b Model    The model object this driver object is for
	 */
	public function __construct(Config $config, Database $database, Model $model) {
		$this->config   = $config;
		$this->database = $database;
		$this->logger   = Logger::getLogger(get_class($this));
		$this->model    = $model;

		$table = $model->getTableData();
		$this->table           = $table['table'];
		$this->primary_key     = $table['primary_key'];
		$this->columns         = $table['columns'];
		$this->indexes         = $table['indexes'];
		$this->uniques         = $table['uniques'];
		$this->partial_uniques = $table['partial_uniques'];
		$this->foreign_keys    = $table['foreign_keys'];
		$this->citusdb         = $table['citusdb'];
	}
}
