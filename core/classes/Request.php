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

	public function setSiteParams($site_params) {
		$this->site_params = $site_params;
	}

	public function getSiteParams() {
		return $this->site_params;
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

	public function getURL($controller_name = NULL, $method_name = NULL, array $params = NULL) {
		if (!$controller_name) $controller_name = 'Information';
		if (!$method_name)     $method_name     = 'index';
		if (!$params)          $params          = [];

		if ($controller_name == 'Information' && $method_name == 'index' && count($params) == 0) {
			return '/';
		}

		$params_string = '';
		foreach ($params as $value) {
			$params_string .= urlencode($value).'/';
		}
		if (strlen($params_string) > 0) $params_string = substr($params_string, 0, -1);

		$url = '/'.$controller_name;
		if ($method_name != 'index' || count($params) > 0) {
			$url .= '/'.$method_name;
		}
		if (count($params) > 0) {
			$url .= '/'.$params_string;
		}
		return $url;
	}

	public function getSecureURL($controller = NULL, $method = NULL, array $params = NULL) {
		throw new \Exception('TODO');
	}

	public function currentURL(array $params = NULL) {
		if ($params === NULL) {
			$params = $this->method_params;
		}

		$class_parts = explode('\\', $this->controller_class);
		$controller_class = $class_parts[count($class_parts)-1];

		return $this->getURL($controller_class, $this->method_name, $params);
	}

	public function getInformationURL($page) {
		$page = str_replace('_', '-', $page);
		return $this->getURL('Information', 'page').'/'.$page;
	}
}
