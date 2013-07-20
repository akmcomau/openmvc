<?php

namespace core\controllers;

use core\classes\Controller;

class Error extends Controller {

	public function error_404() {
		header("HTTP/1.0 404 Not Found");
		$this->response->setContent('404 Page not found');
	}
}