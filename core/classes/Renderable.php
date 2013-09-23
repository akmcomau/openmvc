<?php

namespace core\classes;

use ErrorException;
use core\classes\Config;
use core\classes\Database;
use core\classes\Logger;
use core\classes\exceptions\RenderableException;

abstract class Renderable {

	protected $config;
	protected $database;
	protected $logger;

	public function __construct(Config $config, Database $database) {
		$this->config   = $config;
		$this->database = $database;
		$this->logger   = Logger::getLogger(get_class($this));
	}

	public function getConfig() {
		return $this->config;
	}

	public function getDatabase() {
		return $this->database;
	}

	abstract public function render();
}
