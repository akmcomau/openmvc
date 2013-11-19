<?php

namespace core\classes;

use core\classes\URL;
use core\classes\Request;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Database;

class Hook {

	protected $config;
	protected $database;
	protected $request;
	protected $logger;
	protected $url;

	public function __construct(Config $config, Database $database, Request $request = NULL) {
		$this->config   = $config;
		$this->database = $database;
		$this->request  = $request;
		$this->url      = new URL($config);
		$this->logger   = Logger::getLogger(get_class($this));
	}

}