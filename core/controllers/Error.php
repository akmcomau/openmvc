<?php

namespace core\controllers;

use core\classes\renderable\Controller;

class Error extends Controller {

	public function error_401() {
		header("HTTP/1.1 401 Permission Denied");
		$this->response->setContent('401 Permission Denied');
	}

	public function error_404() {
		header("HTTP/1.1 404 Not Found");
		$this->response->setContent('404 Page not found');
	}

	public function error_500() {
		header("HTTP/1.1 500 Internal Server Error");
		$this->response->setContent('500 Internal Server Error');
	}
}