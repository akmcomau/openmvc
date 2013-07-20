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
		$this->logger   = Logger::getLogger(__CLASS__);

		$layout_class    = $request->getSiteParams()->layout_class;
		$layout_template = $request->getSiteParams()->layout_template;
		$this->layout    = new $layout_class($config, $database, $request, $response, $layout_template);
	}

	public function getURL($controller_name = NULL, $method_name = NULL, array $params = NULL) {
		return $this->request->getURL($controller_name, $method_name, $params);
	}

	public function getSecureURL($controller_name = NULL, $method_name = NULL, array $params = NULL) {
		return $this->request->getSecureURL($controller_name, $method_name, $params);
	}

	public function getCurrentURL(array $params = NULL) {
		return $this->request->currentURL($params);
	}

	public function getInformationURL($page) {
		return $this->request->getInformationURL($page);
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
