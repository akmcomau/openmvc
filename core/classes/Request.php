<?php

namespace core\classes;

class Request {

	public $get_params;
	public $post_params;
	public $request_params;
	public $server_params;
	public $session;

	protected $site_params = NULL;
	protected $controller_class = NULL;
	protected $method_name = NULL;

	protected $authentication;
	protected $url;
	protected $config;

	public function __construct(Config $config, Database $database) {
		$this->config = $config;
		$this->get_params = $_GET;
		$this->post_params = $_POST;
		$this->request_params = $_REQUEST;
		$this->server_params = $_SERVER;
		$this->session = new Session();
		$this->authentication = new Authentication($config, $database, $this);
		$this->url = new URL($config);
	}

	public function getConfig() {
		return $this->config;
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

	public function serverParam($name) {
		if (isset($this->server_params[$name])) {
			return $this->server_params[$name];
		}
		else {
			return NULL;
		}
	}

	public function currentURL(array $params = NULL) {
		$controller_class = $this->url->getControllerClassName($this->controller_class);
		return $this->url->getURL($controller_class, $this->method_name, $params);
	}

}
