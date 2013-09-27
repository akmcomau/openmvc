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

	public function __construct(Config $config, Database $database) {
		$this->config   = $config;
		$this->database = $database;
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
		if (!method_exists($controller, $request->getMethodName())) {
			$this->logger->debug("Controller Not found: $controller_class => ".$request->getMethodName());
			$request->setControllerClass('\\core\\controllers\\Error');
			$request->setMethodName('error_404');
			$request->setMethodParams([]);
			return $this->dispatchRequest($request);
		}
		$this->logger->debug("Dispatching request to $controller_class => ".$request->getMethodName());
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
			$controller->getLayout()->render();
		}

		return $response;
	}

	protected function getControllerClass(Request $request) {
		$site = $this->config->getSiteParams();
		if ($request->getParam('controller')) {
			$site_controller = '\\sites\\'.$site->namespace.'\\controllers\\'.$request->getParam('controller');
			try {
				if (class_exists($site_controller)) {
					return $site_controller;
				}
			}
			catch (AutoLoaderException $ex) {}
			$core_controller = '\\core\\controllers\\'.$request->getParam('controller');
			try {
				if (class_exists($core_controller)) {
					return $core_controller;
				}
			}
			catch (AutoLoaderException $ex) {}

			return NULL;
		}
		else {
			return '\\core\\controllers\\Information';
		}
	}

	protected function getMethodName(Request $request) {
		if ($request->getParam('method')) {
			return str_replace('-', '_', $request->getParam('method'));
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
