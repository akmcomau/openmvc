<?php

namespace core\classes;

class Authentication {

	private $config;
	private $database;
	private $logger;
	private $request;

	public function __construct(Config $config, Database $database, Request $request) {
		$this->config   = $config;
		$this->database = $database;
		$this->request  = $request;
		$this->logger   = Logger::getLogger(__CLASS__);
	}

}