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

	protected $model;

	protected $primary_key = NULL;

	protected $columns = NULL;

	protected $indexes = [];

	protected $foreign_keys = [];

	protected $uniques = [];

	protected $partial_uniques = [];

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
	}
}
