<?php

namespace core\controllers;

use core\classes\exceptions\SoftRedirectException;
use core\classes\Template;
use core\classes\renderable\Controller;

class Account extends Controller {

	public function index() {
		throw new SoftRedirectException(__CLASS__, 'login');
	}

	public function login() {
		$data = [
			'message'       => NULL,
			'message_class' => NULL,
		];

		$template = new Template($this->config, 'pages/account/login.php', $data);
		$this->response->setContent($template->render());
	}

	public function register() {
		echo '<pre>';
		print_r($this->request->request_params);
		echo '</pre>';

		$response = [];

		// display json content
		$this->response->setJsonContent($this, json_encode($response));
	}
}