<?php

namespace core\classes;

use core\classes\exceptions\SoftRedirectException;
use core\classes\exceptions\AutoLoaderException;
use core\classes\exceptions\DispatcherException;
use core\classes\exceptions\RedirectException;

class Dispatcher {

	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The database object
	 * @var Database $database
	 */
	protected $database;

	/**
	 * The logger object
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * The URL object
	 * @var URL $url
	 */
	protected $url;

	/**
	 * The config object stored as a static variable.
	 * This is required for before/after request hooks
	 * @var Config $static_config
	 */
	protected static $static_config;

	/**
	 * The database object stored as a static variable.
	 * This is required for before/after request hooks
	 * @var Database $static_database
	 */
	protected static $static_database;

	/**
	 * The request object stored as a static variable.
	 * This is required for before/after request hooks
	 * @var Database $static_request
	 */
	protected static $static_request;

	/**
	 * The logger object stored as a static variable.
	 * This is required for before/after request hooks
	 * @var Logger $logger
	 */
	protected static $static_logger;

	/**
	 * Constructor
	 * @param $config   The configuration object
	 */
	public function __construct(Config $config) {
		$this->config   = $config;
		$this->url      = new URL($config);
		$this->logger   = Logger::getLogger(__CLASS__);
	}

	/**
	 * Set the database object
	 * @param $database  The database object
	 */
	public function setDatabase(Database $database) {
		$this->database = $database;
	}

	/**
	 * Route and dispatch the request to controller/method
	 * @param $request  The request to dispatch
	 */
	public function dispatch(Request $request) {
		$this->routeRequest($request);
		return $this->dispatchRequest($request);
	}

	/**
	 * Read the request URL and route the request to controller/method
	 * @param $request  The request to route
	 */
	public function routeRequest(Request $request) {
		if (!$this->url->routeRequest($request)) {
			$controller_class = $this->getControllerClass($request);
			$request->setControllerClass($controller_class);

			$method_name = $this->getMethodName($request);
			$request->setMethodName($method_name);

			$method_params = $this->getMethodParams($request);
			$request->setMethodParams($method_params);
		}
	}

	/**
	 * Dispatch the request to controller/method
	 * @param $request  The request to route
	 */
	public function dispatchRequest(Request $request) {
		$sub_page = NULL;
		$response = new Response();

		// get the controller class name
		$controller_class = $request->getControllerClass();
		if (!$controller_class) {
			$this->logger->debug("Controller Not found: ".$request->getParam('controller'));
			return $this->error404($request);
		}

		// Create the controller object and check the method name
		$controller = new $controller_class($this->config, $this->database, $request, $response);
		$controller->setUrl($this->url);
		$full_method_name = $method_name = $request->getMethodName();
		$methods = $controller->getAllMethods();
		if (!$controller->RespondToAllMethods() && !in_array($method_name, $methods)) {
			$this->logger->debug("Method Not found: $controller_class::".$request->getMethodName());
			return $this->error404($request);
		}

		// special rule for mapping CMS pages
		if (preg_match('/^page\/(.*)$/', $method_name, $matches)) {
			$full_method_name = $method_name;
			$method_name = 'page';
			$request->setMethodName('page');
			$request->setMethodParams([$matches[1]]);
			$sub_page = $matches[1];
		}

		$this->logger->debug("Dispatching request to $controller_class::$method_name");

		// check for force password change flag
	    if ($controller->getAuthentication()->forcePasswordChangeEnabled()) {
			if (!preg_match('/\Customer$/', $controller_class) || $method_name != 'change_password') {
				throw new RedirectException($this->url->getUrl('Customer', 'change_password'));
			}
		}

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
				$request->setMethodParams([ $request->getParam('controller'), $request->getParam('method'), $request->getParam('params') ]);
				$request->setControllerClass($this->url->getControllerClass($login_controller));
				$request->setMethodName('login_register');
				return $this->dispatchRequest($request);
			}
		}

		// if this is an admin page or the admin is logged in, then allow even during offline/maintenance modes
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

		// check if admin site is disabled
		if (($is_admin_required || $is_admin_method) && !$this->config->siteConfig()->enable_admin) {
			$this->logger->info("Admin is disabled");
			return $this->error404($request);
		}

		// check if public site is disabled
		if (!($is_admin_required || $is_admin_method) && !$this->config->siteConfig()->enable_public && !preg_match('/^error/', $method_name)) {
			$this->logger->info("Public is disabled");
			return $this->error404($request);
		}

		// Dispatch the request and handle redirections
		try {
			$request->clearDispatcherParams();
			call_user_func_array([$controller, $request->getMethodName()], $request->getMethodParams());
		}
		catch (SoftRedirectException $ex) {
			$this->logger->debug('Soft Redirect to '.$ex->getController().' => '.$ex->getMethod());
			$request->setControllerClass($ex->getController());
			$request->setMethodName($ex->getMethod());
 			$request->setMethodParams($ex->getParams());
			return $this->dispatchRequest($request);
		}
		catch (RedirectException $ex) {
			$this->logger->info($ex->getMessage());
			header("Location: {$ex->getUrl()}");
		}
		$controller->render();

		// render out the page layout
		if ($controller->getLayout()) {
			$class = explode('\\', $controller_class);
			$class = $class[count($class)-1];
			$meta_tags = $this->url->getMethodMetaTags($class, $full_method_name);
			$controller->getLayout()->addMetaTags($meta_tags);
			$controller->getLayout()->setControllerMethod($this->url->getControllerClassName($request->getControllerName()), $request->getMethodName(), $sub_page);
			$controller->getLayout()->render();
		}

		return $response;
	}

	/**
	 * Set the request to the Error 404 page and dispatch it
	 * @param $request  The request object
	 */
	protected function error404(Request $request) {
		$request->setControllerClass($this->url->getControllerClass('Root'));
		$request->setMethodName('error404');
		$request->setMethodParams([]);
		return $this->dispatchRequest($request);
	}

	/**
	 * Gets the full namepsace for the controller class for this request URL
	 * @param $request  The request object
	 */
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
				$this->logger->debug("Controller not found, using Root: ".join('\\', $controller_parts));
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
			return $this->url->getControllerClass('Root');
		}
	}

	/**
	 * Gets the method name for this request URL
	 * @param $request  The request object
	 */
	protected function getMethodName(Request $request) {
		if ($request->getParam('controller') && $request->getParam('method')) {
			$controller = $this->url->getControllerClassName($request->getParam('controller'));
			return $this->url->getMethodName($controller, $request->getParam('method'));
		}
		else {
			return 'index';
		}
	}

	/**
	 * Gets the method parameters name for this request URL.
	 * @param $request  The request object
	 */
	protected function getMethodParams(Request $request) {
		if ($request->getParam('params')) {
			return explode('/', $request->getParam('params'));
		}
		else {
			return [];
		}
	}

	/**
	 * Execute the 'Before Request' hooks
	 * @param $config   The configuration object
	 * @param $database The database object
	 * @param $request  The request object
	 */
	public static function beforeRequest(Config $config, Database $database, Request $request) {
		self::$static_logger   = Logger::getLogger(__CLASS__);
		self::$static_config   = $config;
		self::$static_database = $database;
		self::$static_request  = $request;
		$modules = (new Module($config))->getEnabledModules();
		foreach ($modules as $module) {
			if (isset($module['hooks']['request']['before_request'])) {
				$class = $module['namespace'].'\\'.$module['hooks']['request']['before_request'];
				self::$static_logger->debug("Calling Hook: $class::before_request");
				$class = new $class($config, $database, $request);
				call_user_func_array(array($class, 'before_request'), [$request]);
			}
		}
	}

	/**
	 * Execute the 'After Request' hooks
	 */
	public static function afterRequest() {
		if (self::$static_config) {
			$modules = (new Module(self::$static_config))->getEnabledModules();
			foreach ($modules as $module) {
				if (isset($module['hooks']['request']['after_request'])) {
					$class = $module['namespace'].'\\'.$module['hooks']['request']['after_request'];
					self::$static_logger->debug("Calling Hook: ".get_class($class)."::after_request");
					$class = new $class(self::$static_config, self::$static_database, self::$static_request);
					call_user_func_array(array($class, 'after_request'), [self::$static_request]);
				}
			}
		}
	}
}
