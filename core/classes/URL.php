<?php

namespace core\classes;

use core\classes\URL;

class URL {
	protected $url;

	public function __construct(Config $config) {
		$this->config = $config;
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

	public function getInformationURL($page) {
		$page = str_replace('_', '-', $page);
		return $this->getURL('Information', 'page').'/'.$page;
	}
}
