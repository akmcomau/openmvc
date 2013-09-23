<?php

namespace core\classes;

class Request {

	public $get_params;
	public $post_params;
	public $request_params;
	public $server_params;

	private $site_params = NULL;
	private $controller_class = NULL;
	private $method_name = NULL;

	public $session;

	public $authentication;

	public function __construct(Config $config, Database $database) {
		$this->get_params = $_GET;
		$this->post_params = $_POST;
		$this->request_params = $_REQUEST;
		$this->server_params = $_SERVER;
		$this->session = new Session();
		$this->authentication = new Authentication($config, $database, $this);
	}

	public function setControllerClass($controller_class) {
		$this->controller_class = $controller_class;
	}

	public function getControllerClass() {
		return $this->controller_class;
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

	public function getMethodParams() {
		return $this->method_params;
	}

	public function getParam($name) {
		if (isset($this->get_params[$name])) {
			return $this->get_params[$name];
		}
		else {
			return NULL;
		}
	}

	public function postParam($name) {
		if (isset($this->post_params[$name])) {
			return $this->post_params[$name];
		}
		else {
			return NULL;
		}
	}

	public function requestParam($name) {
		if (isset($this->request_params[$name])) {
			return $this->request_params[$name];
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

	public function currentURL(array $params = NULL) {
		if ($params === NULL) {
			$params = $this->method_params;
		}

		$class_parts = explode('\\', $this->controller_class);
		$controller_class = $class_parts[count($class_parts)-1];

		return $this->getURL($controller_class, $this->method_name, $params);
	}
}
