<?php

namespace core\controllers;

use core\classes\exceptions\SoftRedirectException;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;

class Account extends Controller {

	public function index() {
		throw new SoftRedirectException(__CLASS__, 'login');
	}

	public function login() {
		$form_register = $this->getRegisterForm();
		$form_login    = $this->getLoginForm();

		if ($form_login->validate()) {

		}

		if ($form_register->validate()) {

		}

		$data = [
			'register' => $form_register,
			'login'    => $form_login,
		];

		$template = new Template($this->config, 'pages/account/login.php', $data);
		$this->response->setContent($template->render());
	}

	protected function getRegisterForm() {
		$inputs = [
			'first-name' => [
				'type' => 'string',
				'min_length' => 2,
				'max_length' => 32,
				'message' => 'Between 2-32 characters',
			],
			'last-name' => [
				'type' => 'string',
				'min_length' => 2,
				'max_length' => 32,
				'message' => 'Between 2-32 characters',
			],
			'email' => [
				'type' => 'email',
				'message' => 'Please enter a valid email address',
			],
			'username' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => "Between 6-32 characters"
			],
			'password1' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => "Between 6-32 characters<br />With at least one number"
			],
			'password2' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => "Between 6-32 characters<br />With at least one number"
			]
		];

		$validators = [
			'password1' => [
				[
					'type'    => 'params-equal',
					'param'   => 'password2',
					'message' => 'Passwords do not match',
				],
				[
					'type'      => 'regex',
					'regex'     => '\d',
					'modifiers' => '',
					'message'   => 'Password must contain at least one number',
				],
			],
		];

		return new FormValidator($this->request, 'form-register', $inputs, $validators);
	}

	protected function getLoginForm() {
		$inputs = [
			'username' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => 'Between 6-32 characters',
			],
			'password' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => 'Between 6-32 characters<br />With at least one number',
			],
		];

		return new FormValidator($this->request, 'form-login', $inputs);
	}
}