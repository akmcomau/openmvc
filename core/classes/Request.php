<?php

namespace core\classes;
use core\classes\exceptions\RedirectException;

class Request {

	public $get_params;
	public $post_params;
	public $request_params;
	public $server_params;
	public $cookies;
	public $session;
	public $file_params = NULL;

	protected $site_params = NULL;
	protected $controller_class = NULL;
	protected $method_name = NULL;
	protected $method_params = NULL;
	protected $database = NULL;

	protected $events = [];

	protected $authentication;
	protected $url;
	protected $config;

	public function __construct(Config $config) {
		$this->config = $config;
		$this->get_params = &$_GET;
		$this->post_params = &$_POST;
		$this->request_params = &$_REQUEST;
		$this->server_params = &$_SERVER;
		$this->file_params = &$_FILES;
		$this->cookies = &$_COOKIE;
		$this->session = new Session();
		$this->url = new URL($config);
	}

	public function getConfig() {
		return $this->config;
	}

	public function setDatabase(Database $database) {
		$this->database = $database;
		$this->authentication = new Authentication($this->config, $database, $this);
	}

	public function getDatabase() {
		return $this->database;
	}

	public function getAuthentication() {
		return $this->authentication;
	}

	public function setControllerClass($controller_class) {
		$this->controller_class = $controller_class;
	}

	public function getControllerClass() {
		return $this->controller_class;
	}

	public function getControllerName() {
		return $this->url->getControllerClassName($this->controller_class);
	}

	public function setMethodName($method_name) {
		$this->method_name = $method_name;
	}

	public function getMethodName() {
		return $this->method_name;
	}

	public function setMethodParams($method_params) {
		$this->method_params = $method_params;
	}

	public function verifyMethodParams($method_params) {
		if (count(array_diff($method_params, $this->method_params))) {
			$this->method_params = $method_params;
			throw new RedirectException($this->currentUrl());
		}
	}

	public function getMethodParams() {
		return $this->method_params;
	}

	public function getParam($name, $value = NULL) {
		if ($value) {
			$this->get_params[$name] = $value;
		}

		if (isset($this->get_params[$name])) {
			return $this->get_params[$name];
		}
		else {
			return NULL;
		}
	}

	public function postParam($name, $value = NULL) {
		if ($value) {
			$this->post_params[$name] = $value;
		}

		if (isset($this->post_params[$name])) {
			return $this->post_params[$name];
		}
		else {
			return NULL;
		}
	}

	public function requestParam($name, $value = NULL) {
		if ($value) {
			$this->request_params[$name] = $value;
		}

		if (isset($this->request_params[$name])) {
			return $this->request_params[$name];
		}
		else {
			return NULL;
		}
	}

	public function fileParam($name, $value = NULL) {
		if ($value) {
			$this->file_params[$name] = $value;
		}

		if (isset($this->file_params[$name])) {
			return $this->file_params[$name];
		}
		else {
			return NULL;
		}
	}

	public function serverParam($name) {
		if (isset($this->server_params[$name])) {
			return $this->server_params[$name];
		}
		else {
			return NULL;
		}
	}

	public function currentUrl(array $params = NULL) {
		if (is_null($params)) {
			$params = $this->method_params;
		}
		$controller_class = $this->url->getControllerClassName($this->controller_class);
		$query_string = $this->serverParam('QUERYSTRING');
		$query_string = $query_string ? '?'.$query_string : '';
		return $this->url->getUrl($controller_class, $this->method_name, $params).$query_string;
	}

	public function clearDispatcherParams() {
		unset($this->get_params['method']);
		unset($this->get_params['controller']);
		unset($this->get_params['params']);
	}

	public function getEvents() {
		return $this->events;
	}

	public function addEvent($name, $int = NULL, $double = NULL, $text = NULL) {
		$this->events[] = [
			'name'  => $name,
			'int' => $int,
			'double' => $double,
			'text'  => $text
		];
	}
}
