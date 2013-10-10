<?php

namespace core\classes;

use core\classes\exceptions\SoftRedirectException;
use core\classes\exceptions\AutoLoaderException;
use core\classes\exceptions\DispatcherException;
use core\classes\exceptions\RedirectException;

class Dispatcher {

	protected $config;
	protected $database;
	protected $logger;
	protected $url;

	public function __construct(Config $config, Database $database) {
		$this->config   = $config;
		$this->database = $database;
		$this->url      = new URL($config);
		$this->logger   = Logger::getLogger(__CLASS__);
	}

	public function dispatch(Request $request) {
		$controller_class = $this->getControllerClass($request);
		$request->setControllerClass($controller_class);

		$method_name = $this->getMethodName($request);
		$request->setMethodName($method_name);

		$method_params = $this->getMethodParams($request);
		$request->setMethodParams($method_params);

		return $this->dispatchRequest($request);
	}

	public function dispatchRequest(Request $request) {
		$sub_page = NULL;
		$response = new Response();
		$controller_class = $request->getControllerClass();

		if (!$controller_class) {
			$this->logger->debug("Controller Not found: ".$request->getParam('controller'));
			return $this->error_404($request);
		}
		$controller = new $controller_class($this->config, $this->database, $request, $response);
		$controller->setUrl($this->url);
		$method_name = $request->getMethodName();
		$methods = $controller->getAllMethods();
		if (!in_array($method_name, $methods)) {
			$this->logger->debug("Method Not found: $controller_class::".$request->getMethodName());
			return $this->error_404($request);
		}

		if (preg_match('/^page\/(.*)$/', $method_name, $matches)) {
			$method_name = 'page';
			$request->setMethodName('page');
			$request->setMethodParams([$matches[1]]);
			$sub_page = $matches[1];
		}

		$this->logger->debug("Dispatching request to $controller_class::$method_name");

		// check permissions
		$is_admin_required = FALSE;
		$is_customer_logged_in = FALSE;
		if (isset($controller->getPermissions()[$method_name])) {
			$authenticated    = FALSE;
			$login_controller = NULL;
			foreach ($controller->getPermissions()[$method_name] as $auth_type) {
				switch ($auth_type) {
					case 'administrator':
						if ($controller->getAuthentication()->administratorLoggedIn()) {
							$authenticated = TRUE;
							$is_admin_required = TRUE;
						}
						elseif (empty($login_controller)) {
							$login_controller = 'Administrator';
							$is_customer_logged_in = TRUE;
						}
						break;

					case 'customer':
						if ($controller->getAuthentication()->customerLoggedIn()) {
							$authenticated = TRUE;
						}
						elseif (empty($login_controller)) {
							$login_controller = 'Customer';
						}
						break;
				}
			}
			if (!$authenticated) {
				throw new RedirectException($this->url->getURL($login_controller, 'login'));
			}
		}

		$admin_class = $this->url->getControllerClass('Administrator');
		$is_admin_method = ($method_name == 'login' && $controller_class == $admin_class);

		if (!($is_admin_required || $controller->getAuthentication()->administratorLoggedIn()) && !$is_admin_method) {
			if ($this->config->siteConfig()->site_offline_mode) {
				$template = $controller->getTemplate('pages/site_offline.php');
				$response->setContent($template->render());
				return $response;
			}
			elseif ($this->config->siteConfig()->site_maintenance_mode) {
				$template = $controller->getTemplate('pages/site_maintenance.php');
				$response->setContent($template->render());
				return $response;
			}
		}

		if (($is_admin_required || $is_admin_method) && !$this->config->siteConfig()->enable_admin) {
			$this->logger->debug("Admin is disabled");
			return $this->error_404($request);
		}

		if (!($is_admin_required || $is_admin_method) && !$this->config->siteConfig()->enable_public && !preg_match('/^error_/', $method_name)) {
			$this->logger->debug("Public is disabled");
			return $this->error_404($request);
		}

		try {
			call_user_func_array([$controller, $request->getMethodName()], $request->getMethodParams());
		}
		catch (SoftRedirectException $ex) {
			$this->logger->debug('Soft Redirect to '.$ex->getController().' => '.$ex->getMethod());
			$request->setControllerClass($ex->getController());
			$request->setMethodName($ex->getMethod());
 			$request->setMethodParams($ex->getParams());
			return $this->dispatchRequest($request);
		}
		$controller->render();

		if ($controller->getLayout()) {
			$class = explode('\\', $controller_class);
			$class = $class[count($class)-1];
			$meta_tags = $this->url->getMethodMetaTags($class, $method_name);
			$controller->getLayout()->addMetaTags($meta_tags);
			$controller->getLayout()->setControllerMethod($this->url->getControllerClassName($request->getControllerName()), $request->getMethodName(), $sub_page);
			$controller->getLayout()->render();
		}

		return $response;
	}

	protected function error_404($request) {
		$request->setControllerClass($this->url->getControllerClass('Root'));
		$request->setMethodName('error_404');
		$request->setMethodParams([]);
		return $this->dispatchRequest($request);
	}

	protected function getControllerClass(Request $request) {
		$site = $this->config->siteConfig();
		if ($request->getParam('controller')) {
			$controller = $request->getParam('controller');
			$controller_parts = explode('\\', $controller, 2);
			$controller = $this->url->getControllerClassName($controller);
			$controller = $this->url->getControllerClass($controller);

			if (!empty($controller)) {
				return $controller;
			}
			elseif (count($controller_parts) == 2) {
				$controller = $controller_parts[0];
				$controller = $this->url->getControllerClassName($controller);
				$controller = $this->url->getControllerClass($controller);

				$params = $request->getParam('method');
				if ($request->getParam('params')) {
					$params .= '/'.$request->getParam('params');
				}
				$request->getParam('params', $params);
				$request->getParam('method', $controller_parts[1]);
				$request->getParam('controller', $controller_parts[0]);

				return $controller;
			}
			if (empty($controller)) {
				$root_class  = $this->url->getControllerClass('Root');
				$root_method = $this->url->getMethodName($root_class, $request->getParam('controller'));
				if ($root_method) {
					$params = $request->getParam('method');
					if ($request->getParam('params')) {
						$params .= '/'.$request->getParam('params');
					}
					$request->getParam('params', $params);
					$request->getParam('method', $request->getParam('controller'));
					$request->getParam('controller', 'Root');
					return $root_class;
				}
			}
			return NULL;
		}
		else {
			return '\\core\\controllers\\Root';
		}
	}

	protected function getMethodName(Request $request) {
		if ($request->getParam('controller') && $request->getParam('method')) {
			$controller = $this->url->getControllerClassName($request->getParam('controller'));
			return $this->url->getMethodName($controller, $request->getParam('method'));
		}
		else {
			return 'index';
		}
	}

	protected function getMethodParams(Request $request) {
		if ($request->getParam('params')) {
			return explode('/', $request->getParam('params'));
		}
		else {
			return [];
		}
	}
}
