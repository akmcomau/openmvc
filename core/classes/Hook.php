<?php

namespace core\classes;

use core\classes\URL;
use core\classes\Request;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Database;

class Hook {

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
	 * The request object
	 * @var Request $request
	 */
	protected $request;

	/**
	 * The logger object
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * The URL object
	 * @var URL $url
	 */
	protected $url;

	/**
	 * Constructor
	 * @param[in] $config   \b Config   The configuration object
	 * @param[in] $database \b Database The database object
	 * @param[in] $request  \b Request  The request object
	 */
	public function __construct(Config $config, Database $database, Request $request = NULL) {
		$this->config   = $config;
		$this->database = $database;
		$this->request  = $request;
		$this->url      = new URL($config);
		$this->logger   = Logger::getLogger(get_class($this));
	}

}
