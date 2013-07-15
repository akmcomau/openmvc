<?php

namespace core\controllers;

use core\classes\exceptions\RedirectException;
use core\classes\Controller;
use core\classes\Template;

class Information extends Controller {

	public function index() {
		$template = new Template($this, 'pages/homepage.php');
		$this->response->setContent($template->render());
	}

	public function page($page_name = NULL) {
		// go to homepage if there is no page
		if (!$page_name) {
			throw new RedirectException($this->request->getURL());
		}

		$this->response->setContent('page conroller: '.$page_name);
	}
}