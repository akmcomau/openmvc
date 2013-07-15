<?php

namespace core\controllers;

use core\classes\Controller;

class Error extends Controller {

	public function error_404() {
		$this->response->setContent('404 Page not found');
	}
}