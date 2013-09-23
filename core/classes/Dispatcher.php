<?php

namespace core\classes;

use core\classes\exceptions\AutoLoaderException;
use core\classes\exceptions\DispatcherException;
use core\classes\exceptions\RedirectException;

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
		$site_params = $this->getSiteFromRequest($request);
		$this->config->setDomain($site_params->domain);

		$controller_class = $this->getControllerClass($request);
		$request->setControllerClass($controller_class);

		$method_name = $this->getMethodName($request);
		$request->setMethodName($method_name);

		$method_params = $this->getMethodParams($request);
		$request->setMethodParams($method_params);

		try {
			return $this->dispatchRequest($request);
		}
		catch (AutoLoaderException $ex) {
			$request->setControllerClass('\\core\\controllers\\Error');
			$request->setMethodName('error_404');
			return $this->dispatchRequest($request);
		}
	}

	private function dispatchRequest($request) {
		$response = new Response();
		$controller_class = $request->getControllerClass();
		$controller = new $controller_class($this->config, $this->database, $request, $response);
		if (!method_exists($controller, $request->getMethodName())) {
			$request->setControllerClass('\\core\\controllers\\Error');
			$request->setMethodName('error_404');
			$request->setMethodParams([]);
			return $this->dispatchRequest($request);
		}
		call_user_func_array([$controller, $request->getMethodName()], $request->getMethodParams());
		$controller->render();

		if ($controller->getLayout()) {
			$controller->getLayout()->render();
		}

		return $response;
	}

	private function getControllerClass(Request $request) {
		$site = $this->config->getSiteParams();
		if ($request->getParam('controller')) {
			$site_controller = '\\sites\\'.$site->namespace.'\\controllers\\'.$request->getParam('controller');
			try {
				if (class_exists($site_controller)) {
					return $site_controller;
				}
			}
			catch (AutoLoaderException $ex) {}
			return '\\core\\controllers\\'.$request->getParam('controller');
		}
		else {
			return '\\core\\controllers\\Information';
		}
	}

	private function getMethodName(Request $request) {
		if ($request->getParam('method')) {
			return $request->getParam('method');
		}
		else {
			return 'index';
		}
	}

	private function getMethodParams(Request $request) {
		if ($request->getParam('params')) {
			return explode('/', $request->getParam('params'));
		}
		else {
			return [];
		}
	}

	private function getSiteFromRequest(Request $request) {
		$host  = $request->serverParam('HTTP_HOST');
		$sites = $this->config->sites;
		foreach ($sites as $domain => $site) {
			if ($domain == $host || 'www.'.$domain == $host) {
				$site->domain = $domain;
				return $site;
			}
		}
		throw new DispatcherException("HTTP_HOST does not reference a site");
	}
}
