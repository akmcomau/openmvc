<?php

namespace core\classes;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\AutoLoaderException;

class Dispatcher {

	private $config;
	private $database;
	private $logger;

	public function __construct(Config $config, Database $database) {
		$this->config   = $config;
		$this->database = $database;
		$this->logger   = Logger::getLogger(__CLASS__);
	}

	public function dispatch(Request $request) {
		$controller_name = $this->getControllerName($request);
		$method_name     = $this->getMethodName($request);
		$method_params   = $this->getMethodParams($request);

		try {
			$response = new Response();
			$controller = new $controller_name($this->config, $this->database, $request, $response);
			if (!method_exists($controller, $method_name)) {
				throw new RedirectException($request->getURL('Error', 'error_404'));
			}
			call_user_func_array([$controller, $method_name], $method_params);

			if ($controller->getLayout()) {
				$controller->getLayout()->render();
			}
		}
		catch (AutoLoaderException $ex) {
			throw new RedirectException($request->getURL('Error', 'error_404'));
		}

		return $response;
	}

	private function getControllerName($request) {
		if ($request->getParam('controller')) {
			return '\\core\\controllers\\'.$request->getParam('controller');
		}
		else {
			return '\\core\\controllers\\Information';
		}
	}

	private function getMethodName($request) {
		if ($request->getParam('controller')) {
			return $request->getParam('method');
		}
		else {
			return 'index';
		}
	}

	private function getMethodParams($request) {
		if ($request->getParam('params')) {
			return explode('/', $request->getParam('params'));
		}
		else {
			return [];
		}
	}
}