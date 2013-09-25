<?php

namespace core\classes\exceptions;

use Exception;

class SoftRedirectException extends Exception {
	protected $controller;
	protected $method;
	protected $params;

	public function __construct($controller, $method, array $params = []) {
		$this->controller = $controller;
		$this->method = $method;
		$this->params = $params;
		parent::__construct("Soft Redirect to: $controller => $method");
	}

	public function getController() {
		return $this->controller;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getParams() {
		return $this->params;
	}
}
