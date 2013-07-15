<?php

namespace core\classes;

use core\classes\Config;
use core\classes\Database;
use core\classes\Logger;
use core\classes\Response;
use core\classes\Request;
use core\classes\Layout;

class Controller {

	protected $config;
	protected $database;
	protected $logger;
	protected $request;
	protected $response;
	protected $layout;

	public function __construct(Config $config, Database $database, Request $request, Response $response) {
		$this->config   = $config;
		$this->database = $database;
		$this->request  = $request;
		$this->response = $response;
		$this->layout   = new Layout($config, $database, $request, $response, 'layouts/default.php');
		$this->logger   = Logger::getLogger(__CLASS__);
	}

	public function getURL($controller_name = NULL, $method_name = NULL, $params = NULL) {
		return $this->request->getURL($controller_name, $method_name, $params);
	}

	public function getSecureURL($controller_name = NULL, $method_name = NULL, $params = NULL) {
		return $this->request->getSecureURL($controller_name, $method_name, $params);
	}

	public function getConfig() {
		return $this->config;
	}

	public function getDatabase() {
		return $this->database;
	}

	public function getRequest() {
		return $this->request;
	}

	public function getLayout() {
		return $this->layout;
	}
}
