<?php

namespace core\classes;

use ErrorException;

class URL {
	protected $url;
	protected $url_map;

	public function __construct(Config $config) {
		$this->config  = $config;
		$this->generateUrlMap();
	}

	protected function generateUrlMap() {
		$controllers = $this->listAllControllers();

		$filename = $this->getUrlsFilename('DefaultMethod');
		require($filename);

		$url_config = [];
		foreach ($controllers as $controller => $class) {
			$_URLS = NULL;
			$filename = $this->getUrlsFilename($controller);
			if ($filename) {
				require($filename);
				if ($_URLS) {
					// add ther default method data to the array
					foreach ($_URLS['methods'] as $method => $data) {
						if (!isset($_URLS['methods'][$method]['meta_tags'])) {
							$_URLS['methods'][$method]['meta_tags'] = [];
						}
						foreach ($_DEFAULT_METHOD['meta_tags'] as $property => $value) {
							if (!isset($data['meta_tags'][$property]) && !is_null($value)) {
								$_URLS['methods'][$method]['meta_tags'][$property] = $value;
							}
						}
					}
					$url_config[$controller] = $_URLS;
				}
			}

			if (!isset($url_config[$controller])) {
				$url_config[$controller] = ['aliases' => [], 'methods' => []];
			}
		}

		$this->url_map = ['reverse' => ['controllers'=>[], 'methods'=>[]]];
		foreach ($url_config as $controller => $data) {
			$this->url_map['forward'][$controller] = $data;

			foreach ($data['aliases'] as $language => $alias) {
				$this->url_map['reverse']['controllers'][$alias] = $controller;
			}
			foreach ($data['methods'] as $method => $data) {
				if (isset($data['aliases'])) {
					foreach ($data['aliases'] as $language => $alias) {
						$this->url_map['reverse']['methods'][$controller][$alias] = $method;
					}
				}
			}
		}

		$this->url_map['controllers'] = $controllers;
	}

	protected function listAllControllers() {
		$site = $this->config->siteConfig();
		$site_controllers = [];
		$core_controllers = [];
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$base_core_path = $root_path.'core'.DS.'controllers'.DS;
		$base_site_path = $root_path.'sites'.DS.$site->namespace.DS.'controllers'.DS;

		$dirs = [''];
		foreach (glob("$base_core_path*", GLOB_ONLYDIR) as $dir) {
			if (preg_match('/\/([\w]+)$/', $dir, $matches)) {
				$dirs[] = $matches[1];
			}
		}
		foreach (glob("$base_site_path*", GLOB_ONLYDIR) as $dir) {
			if (preg_match('/\/([\w]+)$/', $dir, $matches)) {
				if (!in_array($matches[1])) {
					$dirs[] = $matches[1];
				}
			}
		}

		foreach ($dirs as $prefix) {
			$path_prefix = $prefix == '' ? '' : $prefix.DS;
			$class_prefix = $prefix == '' ? '' : $prefix.'\\';
			$core_path = $base_core_path.$path_prefix;
			$site_path = $base_site_path.$path_prefix;

			foreach (glob("$site_path*.php") as $filename) {
				if (preg_match('/\/([\w]+)\.php$/', $filename, $matches)) {
					$site_controllers[$class_prefix.$matches[1]] = '\\sites\\'.$site->namespace.'\\controllers\\'.$class_prefix.$matches[1];
				}
			}

			foreach (glob("$core_path*.php") as $filename) {
				if (preg_match('/\/([\w]+)\.php$/', $filename, $matches)) {
					$core_controllers[$class_prefix.$matches[1]] = '\\core\\controllers\\'.$class_prefix.$matches[1];
				}
			}

			$controllers = $site_controllers;
			foreach ($core_controllers as $controller => $class) {
				if (!isset($controllers[$controller])) {
					$controllers[$controller] = $class;
				}
			}
		}

		return $controllers;
	}

	protected function getUrlsFilename($controller) {
		$site = $this->config->siteConfig();
		$language = $site->language;
		$theme = $site->theme;

		$controller = str_replace('\\', DS, $controller);

		$filename = $controller.'.php';
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$default_path = 'core'.DS.'meta'.DS.$language.DS;
		$default_file = $root_path.$default_path.$filename;
		$site_path = 'sites'.DS.$site->namespace.DS.'meta'.DS.$language.DS;
		$site_file = $root_path.$site_path.$filename;

		if (file_exists($site_file)) {
			return $site_file;
		}
		if (file_exists($default_file)) {
			return $default_file;
		}

		return NULL;
	}

	public function getMethodMetaTags($controller_name = NULL, $method_name = NULL) {
		$meta_tags = [];
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';

		if (isset($this->url_map['forward'][$controller_name]['methods'][$method_name]['meta_tags'])) {
			$meta_tags = $this->url_map['forward'][$controller_name]['methods'][$method_name]['meta_tags'];
		}

		if (!isset($meta_tags['title'])) {
			if (isset($this->url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language])) {
				$meta_tags['title'] = $this->url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language].' | '.$this->config->siteConfig()->name;
			}
			else {
				$meta_tags['title'] = $this->config->siteConfig()->name;
			}
		}

		return $meta_tags;
	}

	public function getURL($controller_name = NULL, $method_name = NULL, array $params = NULL) {
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';
		if (!$params)          $params          = [];

		if ($controller_name == 'Root' && $method_name == 'index' && count($params) == 0) {
			return '/';
		}

		$params_string = '';
		foreach ($params as $value) {
			$params_string .= urlencode($value).'/';
		}
		if (strlen($params_string) > 0) $params_string = substr($params_string, 0, -1);

		// seo the url
		$orig_method = $method_name;
		$orig_controller = $controller_name;
		if (isset($this->url_map['forward'][$controller_name]['methods'][$method_name]['aliases'][$this->config->siteConfig()->language])) {
			$method_name = $this->url_map['forward'][$controller_name]['methods'][$method_name]['aliases'][$this->config->siteConfig()->language];
		}
		if (isset($this->url_map['forward'][$controller_name]['aliases'][$this->config->siteConfig()->language])) {
			$controller_name = $this->url_map['forward'][$controller_name]['aliases'][$this->config->siteConfig()->language];
		}

		$url = '/';
		if ($orig_controller != 'Root') {
			$url .= $controller_name.'/';
		}
		if ($orig_method != 'index' || count($params) > 0) {
			$url .= $method_name.'/';
		}
		if (count($params) > 0) {
			$url .= $params_string;
		}
		if (preg_match('/\/$/', $url)) {
			$url = substr($url, 0, -1);
		}
		return $url;
	}

	public function getSecureURL($controller = NULL, $method = NULL, array $params = NULL) {
		throw new \Exception('TODO');
	}

	public function getLink($class, $controller_name = NULL, $method_name = NULL, array $params = NULL) {
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';
		$controller_name = str_replace('/', '\\', $controller_name);

		$url = $this->getURL($controller_name, $method_name, $params);
		$text = $controller_name.'::'.$method_name;

		try {
			if ($this->url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language]) {
				$text = $this->url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language];
			}
		}
		catch (ErrorException $ex) {}
		return '<a class="'.$class.'" href="'.$url.'">'.$text.'</a>';
	}

	public function getControllerClass($controller) {
		if (isset($this->url_map['controllers'][$controller])) {
			return $this->url_map['controllers'][$controller];
		}
		return NULL;
	}

	public function getControllerClassName($controller) {
		$controller = str_replace('\\', '/', $controller);
		if (isset($this->url_map['reverse']['controllers'][$controller])) {
			return $this->url_map['reverse']['controllers'][$controller];
		}
		return $controller;
	}

	public function getMethodName($controller, $method) {
		$controller = str_replace('\\', '/', $controller);
		if (isset($this->url_map['reverse']['methods'][$controller][$method])) {
			return $this->url_map['reverse']['methods'][$controller][$method];
		}
		return $method;
	}
}
