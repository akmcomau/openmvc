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

	public function dispatchRequest($request) {
		$response = new Response();
		$controller_class = $request->getControllerClass();

		if (!$controller_class) {
			$this->logger->debug("Controller Not found");
			$request->setControllerClass('\\core\\controllers\\Error');
			$request->setMethodName('error_404');
			return $this->dispatchRequest($request);
		}

		$controller = new $controller_class($this->config, $this->database, $request, $response);
		$method_name = $request->getMethodName();
		if (!method_exists($controller, $method_name)) {
			$this->logger->debug("Controller Not found: $controller_class => ".$request->getMethodName());
			$request->setControllerClass('\\core\\controllers\\Error');
			$request->setMethodName('error_404');
			$request->setMethodParams([]);
			return $this->dispatchRequest($request);
		}
		$this->logger->debug("Dispatching request to $controller_class => $method_name");

		// check permissions
		if (isset($controller->getPermissions()[$method_name])) {
			$authenticated = FALSE;
			foreach ($controller->getPermissions()[$method_name] as $auth_type) {
				switch ($auth_type) {
					case 'administrator':
						if ($controller->getAuthentication()->administratorLoggedIn()) {
							$authenticated = TRUE;
						}
						break;

					case 'customer':
						if ($controller->getAuthentication()->customerLoggedIn()) {
							$authenticated = TRUE;
						}
						break;
				}
			}
			if (!$authenticated) {
				throw new RedirectException($this->url->getURL('Account', 'login'));
			}
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
			$controller->getLayout()->render();
		}

		return $response;
	}

	protected function getControllerClass(Request $request) {
		$site = $this->config->siteConfig();
		if ($request->getParam('controller')) {
			$controller = $request->getParam('controller');
			$controller = $this->url->getControllerClassName($controller);
			$controller = $this->url->getControllerClass($controller);
			return $controller;
		}
		else {
			return '\\core\\controllers\\Information';
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
