<?php

namespace core\classes;

class Request {

	public $get_params;
	public $post_params;
	public $request_params;
	public $server_params;

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

	public function getURL($controller_name = NULL, $method_name = NULL, $params = NULL) {
		if (!$controller_name) $controller_name = 'Information';
		if (!$method_name)     $method_name     = 'index';
		if (!$params)          $params     = [];

		if ($controller_name == 'Information' && $method_name == 'index' && count($params) == 0) {
			return '/';
		}

		$params_string = '';
		foreach ($params as $value) {
			$params_string .= urlencode($value).'/';
		}
		return '/'.$controller_name.'/'.$method_name.'/'.$params_string;
	}

	public function getSecureURL($controller = NULL, $method = NULL, $params = NULL) {
		throw new \Exception('TODO');
	}
}
