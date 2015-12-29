<?php

namespace core\classes;

use ErrorException;

class URL {
	protected static $url_map = NULL;
	protected $logger = NULL;

	public function __construct(Config $config, $gen_url_map = TRUE) {
		$this->config = $config;
		if ($gen_url_map) $this->generateUrlMap();
		$this->logger = Logger::getLogger(__CLASS__);
	}

	public function getUrlMap() {
		return self::$url_map;
	}

	public function usingSSL() {
		if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
			return true;
		}
		return false;
	}

	protected function generateUrlMap() {
		if (self::$url_map) return self::$url_map;

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

						// add all the meta tags from the default list
						foreach ($_DEFAULT_METHOD['meta_tags'] as $property => $prop_data) {
							if (isset($_URLS['methods'][$method]['meta_tags'][$property][$language])) {
								$meta_tags[$property] = $_URLS['methods'][$method]['meta_tags'][$property][$language];
								unset($_URLS['methods'][$method]['meta_tags'][$property]);
							}
							elseif (!isset($meta_tags[$property]) && !is_null($prop_data[$language])) {
								$meta_tags[$property] = $prop_data[$language];
							}
						}

						// add any other meta in the array
						foreach ($_URLS['methods'][$method]['meta_tags'] as $property => $prop_data) {
							if (isset($prop_data[$language])) {
								$meta_tags[$property] = $prop_data[$language];
							}
						}

						$_URLS['methods'][$method]['meta_tags'] = $meta_tags;
					}
					$url_config[$controller] = $_URLS;
				}
			}

			if (!isset($url_config[$controller])) {
				$url_config[$controller] = ['aliases' => [], 'methods' => []];
			}
		}

		self::$url_map = ['reverse' => ['controllers'=>[], 'methods'=>[]]];
		foreach ($url_config as $controller => $data) {
			self::$url_map['forward'][$controller] = $data;

			if (count($data['aliases']) == 0) {
				self::$url_map['reverse']['controllers'][$controller] = $controller;
			}
			foreach ($data['aliases'] as $language => $alias) {
				self::$url_map['reverse']['controllers'][$alias] = $controller;
			}
			foreach ($data['methods'] as $method => $data) {
				if (isset($data['aliases'])) {
					foreach ($data['aliases'] as $language => $alias) {
						self::$url_map['reverse']['methods'][$controller][$alias] = $method;
					}
				}
			}
		}

		foreach ($controllers as $controller => $controller_class) {
			self::$url_map['reverse']['controllers'][$controller_class] = $controller;
		}

		self::$url_map['controllers'] = $controllers;

		self::$url_map['router'] = $this->getRouterConfig();
	}

	public function listAllControllers() {
		$site = $this->config->siteConfig();
		$site_controllers = [];
		$core_controllers = [];
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$base_core_path = $root_path.'core'.DS.'controllers'.DS;
		$base_site_path = $root_path.'sites'.DS.$site->namespace.DS.'controllers'.DS;

		$regex_DS = (DS == '/') ? '\\/' : '\\\\';

		// find all the core and site controller paths
		$dirs = [''];
		foreach (glob("$base_core_path*", GLOB_ONLYDIR) as $dir) {
			if (preg_match('/'.$regex_DS.'([\w]+)$/', $dir, $matches)) {
				$dirs[] = $matches[1];
			}
		}
		foreach (glob("$base_site_path*", GLOB_ONLYDIR) as $dir) {
			if (preg_match('/'.$regex_DS.'([\w]+)$/', $dir, $matches)) {
				if (!in_array($matches[1], $dirs)) {
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
				if (preg_match('/'.$regex_DS.'([\w]+)\.php$/', $filename, $matches)) {
					$site_controllers[$class_prefix.$matches[1]] = '\\sites\\'.$site->namespace.'\\controllers\\'.$class_prefix.$matches[1];
				}
			}

			foreach (glob("$core_path*.php") as $filename) {
				if (preg_match('/'.$regex_DS.'([\w]+)\.php$/', $filename, $matches)) {
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

	protected function getRouterConfig() {
		$site = $this->config->siteConfig();
		$language = $site->language;

		$filename = 'router.php';
		$root_path = __DIR__.DS.'..'.DS.'..';
		$default_path = 'core'.DS.'config'.DS;
		$default_file = $root_path.DS.$default_path.$filename;
		$site_path = 'sites'.DS.$site->namespace.DS.'config'.DS;
		$site_file = $root_path.DS.$site_path.$filename;

		$filename = NULL;
		if (file_exists($site_file)) {
			$filename = $site_file;
		}
		elseif (file_exists($default_file)) {
			$filename = $default_file;
		}

		if ($filename == NULL) {
			return [];
		}

		require($filename);
		return $_ROUTER;
	}

	protected function getUrlsFilename($controller, $class) {
		$site = $this->config->siteConfig();
		$language = $site->language;
		$theme = $site->theme;

		$controller = str_replace('\\', DS, $controller);

		$filename = $controller.'.php';
		$root_path = __DIR__.DS.'..'.DS.'..';
		$default_path = 'core'.DS.'meta'.DS;
		$default_file = $root_path.DS.$default_path.$filename;
		$site_path = 'sites'.DS.$site->namespace.DS.'meta'.DS;
		$site_file = $root_path.DS.$site_path.$filename;

		if (file_exists($site_file)) {
			return $site_file;
		}
		if (file_exists($default_file)) {
			return $default_file;
		}

		$module_path = $root_path.str_replace('\\', DS, str_replace('\\controllers\\', '\\meta\\', $class)).'.php';
		if (file_exists($module_path)) {
			return $module_path;
		}

		return NULL;
	}

	public function getMethodMetaTags($controller_name = NULL, $method_name = NULL, $postfix_site = TRUE, $recursive = FALSE) {
		$meta_tags = [];
		$used_default = FALSE;
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';
		if ($controller_name == 'Root' && $method_name == 'index') {
			$used_default = TRUE;
		}

		if (isset(self::$url_map['forward'][$controller_name]['methods'][$method_name]['meta_tags'])) {
			$meta_tags = self::$url_map['forward'][$controller_name]['methods'][$method_name]['meta_tags'];
		}

		if (count($meta_tags) == 0 && !$recursive) {
			$used_default = TRUE;
			$meta_tags = $this->getMethodMetaTags('Root', 'index', $postfix_site, TRUE);
		}

		if (!isset($meta_tags['title'])) {
			$meta_tags['title'] = $this->config->siteConfig()->name;
		}

		if (!$used_default && !$recursive && $postfix_site) {
			$meta_tags['title'] .= ' :: '.$this->config->siteConfig()->name;
		}

		// create the open graph meta tags
		if (!$recursive && $this->config->siteConfig()->og_meta_tags) {
			$meta_tags['og:type'] = $this->config->siteConfig()->og_type;
			$meta_tags['og:title'] = $meta_tags['title'];
			if (isset($meta_tags['description'])) {
				$meta_tags['og:description'] = $meta_tags['description'];
			}

			if (!isset($meta_tags['og:image'])) {
				$root_meta = $this->getMethodMetaTags('Root', 'index', $postfix_site, TRUE);
				if (isset($root_meta['og:image'])) {
					$meta_tags['og:image'] = $root_meta['og:image'];
				}
			}
		}


		return $meta_tags;
	}

	public function getMethodConfig($controller_name = NULL, $method_name = NULL) {
		$meta_tags = [];
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';

		if (isset(self::$url_map['forward'][$controller_name]['methods'][$method_name])) {
			return self::$url_map['forward'][$controller_name]['methods'][$method_name];
		}

		return NULL;
	}

	public function canonical($string) {
		$string = preg_replace('/[\'"\{\}\[\]\(\)*&\^%\$#@!~`<>*+]/', '', $string);
		$string = preg_replace('/[ \/\|,;:]/', '-', $string);
		$string = preg_replace('/-+/', '-', $string);
		return strtolower($string);
	}

	/**
	 * Gets a static URL, if the connection is SSL, then a SSL URL will be returned
	 * @param[in] $force_ssl \b boolean Get a SSL URL regardless of current connection
	 * @return \b string The sites base URL
	 */
	public function getStaticUrl($url, $force_ssl = FALSE) {
		$url = $this->config->siteConfig()->static_prefix.$url;
		if ($this->config->isHttps() || $force_ssl) {
			return $this->config->getSecureSiteUrl().$url;
		}
		return $this->config->getSiteUrl().$url;
	}

	public function getUrl($controller_name = NULL, $method_name = NULL, array $params = NULL, array $get_params = NULL) {
		if ($this->usingSSL() || ($this->config->siteConfig()->enable_ssl && $this->config->siteConfig()->force_ssl)) {
			return $this->getSecureUrl($controller_name, $method_name, $params, $get_params);
		}
		return $this->config->getSiteUrl().$this->getRelativeUrl($controller_name, $method_name, $params, $get_params);
	}

	public function getSecureUrl($controller_name = NULL, $method_name = NULL, array $params = NULL, array $get_params = NULL) {
		return $this->config->getSecureSiteUrl().$this->getRelativeUrl($controller_name, $method_name, $params, $get_params);
	}

	public function getRelativeUrl($controller_name = NULL, $method_name = NULL, array $params = NULL, array $get_params = NULL) {
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';
		if (!$params)          $params          = [];

		$params_string = '';
		foreach ($params as $value) {
			// not allowed to have '/' characters in the value
			$value = str_replace('/', '-', $value);

			$params_string .= urlencode($value).'/';
		}
		if (strlen($params_string) > 0) $params_string = substr($params_string, 0, -1);

		if (!isset(self::$url_map['forward'][$controller_name])) {
			$controller_name = str_replace('/', '\\', $controller_name);
		}

		$url = $this->rewriteUrl($controller_name, $method_name, $params);
		if (!$url) {
			// seo the url
			$orig_method = $method_name;
			$orig_controller = $controller_name;
			if (isset(self::$url_map['forward'][$controller_name]['methods'][$method_name]['aliases'][$this->config->siteConfig()->language])) {
				$method_name = self::$url_map['forward'][$controller_name]['methods'][$method_name]['aliases'][$this->config->siteConfig()->language];
			}
			if (isset(self::$url_map['forward'][$controller_name]['aliases'][$this->config->siteConfig()->language])) {
				$controller_name = self::$url_map['forward'][$controller_name]['aliases'][$this->config->siteConfig()->language];
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
		}

		// remove trailing slash
		if (preg_match('/\/$/', $url)) {
			$url = substr($url, 0, -1);
		}

		// add get params
		if ($get_params) {
			$url .= '?'.http_build_query($get_params);
		}

		return (strlen($url) == 0) ? '/' : $url;
	}

	public function getLink($class, $controller_name = NULL, $method_name = NULL, array $params = NULL, array $get_params = NULL) {
		$url = $this->getUrl($controller_name, $method_name, $params, $get_params);
		$text = $this->getLinkText($controller_name, $method_name);

		return '<a class="'.$class.'" href="'.$url.'">'.$text.'</a>';
	}

	public function getLinkText($controller_name = NULL, $method_name = NULL) {
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';

		if (!isset(self::$url_map['forward'][$controller_name])) {
			$controller_name = str_replace('/', '\\', $controller_name);
		}

		$text = $controller_name.'::'.$method_name;
		if (isset(self::$url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language])) {
			$text = self::$url_map['forward'][$controller_name]['methods'][$method_name]['link_text'][$this->config->siteConfig()->language];
		}
		return $text;
	}

	public function getCategory($controller_name = NULL, $method_name = NULL) {
		if (!$controller_name) $controller_name = 'Root';
		if (!$method_name)     $method_name     = 'index';

		if (!isset(self::$url_map['forward'][$controller_name])) {
			$controller_name = str_replace('/', '\\', $controller_name);
		}

		$categ = NULL;
		if (isset(self::$url_map['forward'][$controller_name]['methods'][$method_name]['category'])) {
			$categ = self::$url_map['forward'][$controller_name]['methods'][$method_name]['category'];
		}
		return $categ;
	}

	public function seoController($controller) {
		if (!isset(self::$url_map['forward'][$controller])) {
			$controller = str_replace('/', '\\', $controller);
		}
		if (isset(self::$url_map['forward'][$controller]['aliases'][$this->config->siteConfig()->language])) {
			return self::$url_map['forward'][$controller]['aliases'][$this->config->siteConfig()->language];
		}
		$controller = str_replace('\\', '/', $controller);
		return $controller;
	}

	public function seoMethod($controller, $method) {
		if (!isset(self::$url_map['forward'][$controller])) {
			$controller = str_replace('/', '\\', $controller);
		}
		if (isset(self::$url_map['forward'][$controller]['methods'][$method]['aliases'][$this->config->siteConfig()->language])) {
			return self::$url_map['forward'][$controller]['methods'][$method]['aliases'][$this->config->siteConfig()->language];
		}
		return $method;
	}

	public function getControllerClass($controller) {
		if (isset(self::$url_map['controllers'][$controller])) {
			return self::$url_map['controllers'][$controller];
		}
		return NULL;
	}

	public function getControllerClassName($controller) {
		if (!isset(self::$url_map['reverse']['controllers'][$controller])) {
			$controller = str_replace('\\', '/', $controller);
		}
		if (!isset(self::$url_map['reverse']['controllers'][$controller])) {
			$controller = str_replace('/', '\\', $controller);
		}
		if (isset(self::$url_map['reverse']['controllers'][$controller])) {
			return self::$url_map['reverse']['controllers'][$controller];
		}
		return $controller;
	}

	public function getMethodName($controller, $method) {
		if (!isset(self::$url_map['reverse']['methods'][$controller][$method])) {
			$controller = str_replace('\\', '/', $controller);
		}
		if (isset(self::$url_map['reverse']['methods'][$controller][$method])) {
			return self::$url_map['reverse']['methods'][$controller][$method];
		}

		$controller = str_replace('/', '\\', $controller);
		if (isset(self::$url_map['forward'][$controller]['methods'][$method]['aliases'][$this->config->siteConfig()->language])) {
			if (count(self::$url_map['forward'][$controller]['methods'][$method]['aliases'][$this->config->siteConfig()->language])) {
				return NULL;
			}
		}

		return $method;
	}

	public function routeRequest(Request $request) {
		foreach (self::$url_map['router']['forward'] as $regex => $redirect) {
			if (preg_match($regex, $_SERVER['REQUEST_URI'], $matches)) {
				if (is_callable($redirect)) {
					$result = $redirect($request, $matches);
					$request->setControllerClass($this->getControllerClass($result['controller']));
					$request->setMethodName($result['method']);
					$request->setMethodParams($result['params']);

					// log it and return
					if ($this->logger->isDebugEnabled()) {
						$this->logger->debug('Routed request: '.json_encode($redirect).' <== '.json_encode($matches));
					}
					return TRUE;
				}
				elseif (is_array($redirect)) {
					if (preg_match($regex, $_SERVER['REQUEST_URI'], $matches)) {
						// set the controller and method
						$request->setControllerClass($this->getControllerClass($redirect['controller']));
						$request->setMethodName($redirect['method']);

						// get the params
						$params = [];
						if (isset($redirect['params'])) {
							foreach ($redirect['params'] as $index) {
								if (is_integer($index)) {
									$params[] = isset($matches[$index]) ? $matches[$index] : NULL;
								}
								else {
									$params[] = $index;
								}
							}
						}
						$request->setMethodParams($params);

						// log it and return
						if ($this->logger->isDebugEnabled()) {
							$this->logger->debug('Routed request: '.json_encode($redirect).' <== '.json_encode($matches));
						}
						return TRUE;
					}
				}
				else {
					throw new ErrorException('Invalid type for rediect '.$regex.' => '.gettype($redirect));
				}
			}
		}

		return FALSE;
	}

	protected function rewriteUrl($controller, $method, $params) {
		if (isset(self::$url_map['router']['reverse'][$controller][$method])) {
			$rewrite = self::$url_map['router']['reverse'][$controller][$method];
			if (is_callable($rewrite)) {
				return $rewrite($params);
			}
			elseif (is_string($rewrite)) {
				return $rewrite;
			}
			else {
				throw new ErrorException("Invalid type for rewrite $controller :: $method => ".gettype($rewrite));
			}
		}

		return FALSE;
	}
}
