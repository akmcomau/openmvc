<?php

namespace core\classes;

use ErrorException;

class URL {
	protected $url;
	protected $url_map;

	public function __construct(Config $config) {
		$this->config = $config;
		$this->generateUrlMap();
	}

	public function getUrlMap() {
		return $this->url_map;
	}

	protected function generateUrlMap() {
		$controllers = $this->listAllControllers();
		$language    = $this->config->siteConfig()->language;

		$filename = $this->getUrlsFilename('DefaultMethod', '');
		require($filename);

		$url_config = [];
		foreach ($controllers as $controller => $controller_class) {
			$_URLS = NULL;
			$filename = $this->getUrlsFilename($controller, $controller_class);
			if ($filename) {
				require($filename);
				if ($_URLS) {
					// Add references for methods not in here
					$controller_obj = new $controller_class($this->config);
					$controller_obj->setUrl($this);
					foreach ($controller_obj->getAllMethods() as $method) {
						if (!isset($_URLS['methods'][$method])) {
							$_URLS['methods'][$method] = [];
						}
					}

					// add ther default method data to the array
					foreach ($_URLS['methods'] as $method => $data) {
						$meta_tags = [];
						if (!isset($_URLS['methods'][$method]['meta_tags'])) {
							$_URLS['methods'][$method]['meta_tags'] = [];
						}
						foreach ($_DEFAULT_METHOD['meta_tags'] as $property => $prop_data) {
							if (isset($_URLS['methods'][$method]['meta_tags'][$property][$language])) {
								$meta_tags[$property] = $_URLS['methods'][$method]['meta_tags'][$property][$language];
							}
							elseif (!isset($meta_tags[$property]) && !is_null($prop_data[$language])) {
								$meta_tags[$property] = $prop_data[$language];
							}
						}
						$_URLS['methods'][$method]['meta_tags'] = $meta_tags;
					}
					$url_config[$controller] = $_URLS;
				}
			}
			else {
				print $controller_class.'<br />';
			}

			if (!isset($url_config[$controller])) {
				$url_config[$controller] = ['aliases' => [], 'methods' => []];
			}
		}

		$this->url_map = ['reverse' => ['controllers'=>[], 'methods'=>[]]];
		foreach ($url_config as $controller => $data) {
			$this->url_map['forward'][$controller] = $data;

			$this->url_map['reverse']['controllers'][$controller] = $controller;
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

		foreach ($controllers as $controller => $controller_class) {
			$this->url_map['reverse']['controllers'][$controller_class] = $controller;
		}

		$this->url_map['controllers'] = $controllers;
	}

	public function listAllControllers() {
		$site = $this->config->siteConfig();
		$site_controllers = [];
		$core_controllers = [];
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$base_core_path = $root_path.'core'.DS.'controllers'.DS;
		$base_site_path = $root_path.'sites'.DS.$site->namespace.DS.'controllers'.DS;

		// find all the core and site controller paths
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

		// get all the core and site controllers
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

		// get all the module controllers
		$modules = (new Module($this->config))->getModules();
		foreach ($modules as $module) {
			if ($module['enabled']) {
				foreach ($module['controllers'] as $controller) {
					$controllers[$controller] = $module['namespace'].'\\controllers\\'.$controller;
				}
			}
		}

		return $controllers;
	}

	protected function getUrlsFilename($controller, $class) {
		$site = $this->config->siteConfig();
		$language = $site->language;
		$theme = $site->theme;

		$controller = str_replace('\\', DS, $controller);

		$filename = $controller.'.php';
		$root_path = __DIR__.DS.'..'.DS.'..';
		$default_path = 'core'.DS.'meta'.DS.$language.DS;
		$default_file = $root_path.DS.$default_path.$filename;
		$site_path = 'sites'.DS.$site->namespace.DS.'meta'.DS.$language.DS;
		$site_file = $root_path.DS.$site_path.$filename;

		if (file_exists($site_file)) {
			return $site_file;
		}
		if (file_exists($default_file)) {
			return $default_file;
		}

		$module_path = $root_path.str_replace('\\', DS, str_replace('\\controllers\\', '\\meta\\'.$language.'\\', $class)).'.php';
		if (file_exists($module_path)) {
			return $module_path;
		}

		return NULL;
	}

	public function getMethodMetaTags($controller_name = NULL, $method_name = NULL, $postfix_site = TRUE) {
		$meta_tags = [];
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';

		if (isset($this->url_map['forward'][$controller_name]['methods'][$method_name]['meta_tags'])) {
			$meta_tags = $this->url_map['forward'][$controller_name]['methods'][$method_name]['meta_tags'];
		}

		if (!isset($meta_tags['title'])) {
			$postfix = '';
			if ($postfix_site) $postfix = ' :: '.$this->config->siteConfig()->name;
			if (isset($this->url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language])) {
				$meta_tags['title'] = $this->url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language].$postfix;
			}
			else {
				if ($postfix_site) {
					$meta_tags['title'] = $this->config->siteConfig()->name;
				}
				else {
					$meta_tags['title'] = '';
				}
			}
		}

		return $meta_tags;
	}

	public function getURL($controller_name = NULL, $method_name = NULL, array $params = NULL, array $get_params = NULL) {
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

		if (!isset($this->url_map['forward'][$controller_name])) {
			$controller_name = str_replace('/', '\\', $controller_name);
		}

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

		// add parameters
		if (count($params) > 0) {
			$url .= $params_string;
		}

		// remove trailing slash
		if (preg_match('/\/$/', $url)) {
			$url = substr($url, 0, -1);
		}

		// add get params
		if ($get_params) {
			$url .= '?'.http_build_query($get_params);
		}

		return $url;
	}

	public function getSecureURL($controller = NULL, $method = NULL, array $params = NULL) {
		throw new \Exception('TODO');
	}

	public function getLink($class, $controller_name = NULL, $method_name = NULL, array $params = NULL, array $get_params = NULL) {
		$url = $this->getURL($controller_name, $method_name, $params, $get_params);
		$text = $this->getLinkText($controller_name, $method_name);

		return '<a class="'.$class.'" href="'.$url.'">'.$text.'</a>';
	}

	public function getLinkText($controller_name = NULL, $method_name = NULL) {
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';

		if (!isset($this->url_map['forward'][$controller_name])) {
			$controller_name = str_replace('/', '\\', $controller_name);
		}

		$text = $controller_name.'::'.$method_name;
		if (isset($this->url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language])) {
			$text = $this->url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language];
		}
		return $text;
	}

	public function getCategory($controller_name = NULL, $method_name = NULL) {
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';

		if (!isset($this->url_map['forward'][$controller_name])) {
			$controller_name = str_replace('/', '\\', $controller_name);
		}

		$categ = NULL;
		if (isset($this->url_map['forward'][$controller_name]['methods'][$method_name]['category'])) {
			$categ = $this->url_map['forward'][$controller_name]['methods'][$method_name]['category'];
		}
		return $categ;
	}

	public function seoController($controller) {
		if (!isset($this->url_map['forward'][$controller])) {
			$controller = str_replace('/', '\\', $controller);
		}
		if (isset($this->url_map['forward'][$controller]['aliases'][$this->config->siteConfig()->language])) {
			return $this->url_map['forward'][$controller]['aliases'][$this->config->siteConfig()->language];
		}
		$controller = str_replace('\\', '/', $controller);
		return $controller;
	}

	public function seoMethod($controller, $method) {
		if (!isset($this->url_map['forward'][$controller])) {
			$controller = str_replace('/', '\\', $controller);
		}
		if (isset($this->url_map['forward'][$controller]['methods'][$method]['aliases'][$this->config->siteConfig()->language])) {
			return $this->url_map['forward'][$controller]['methods'][$method]['aliases'][$this->config->siteConfig()->language];
		}
		return $method;
	}

	public function getControllerClass($controller) {
		if (isset($this->url_map['controllers'][$controller])) {
			return $this->url_map['controllers'][$controller];
		}
		return NULL;
	}

	public function getControllerClassName($controller) {
		if (!isset($this->url_map['reverse']['controllers'][$controller])) {
			$controller = str_replace('\\', '/', $controller);
		}
		if (isset($this->url_map['reverse']['controllers'][$controller])) {
			return $this->url_map['reverse']['controllers'][$controller];
		}
		return $controller;
	}

	public function getMethodName($controller, $method) {
		if (!isset($this->url_map['reverse']['methods'][$controller][$method])) {
			$controller = str_replace('\\', '/', $controller);
		}
		if (isset($this->url_map['reverse']['methods'][$controller][$method])) {
			return $this->url_map['reverse']['methods'][$controller][$method];
		}
		return $method;
	}
}
