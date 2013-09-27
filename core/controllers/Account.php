<?php

namespace core\controllers;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;

class Account extends Controller {

	public function index() {
		if (!$this->authentication->customerLoggedIn()) {
			throw new SoftRedirectException(__CLASS__, 'login');
		}
	}

	public function logout() {
		$this->authentication->logout();
		throw new RedirectException($this->url->getURL());
	}

	public function login() {
		$bcrypt_cost   = $this->config->getSiteParams()->bcrypt_cost;
		$model         = new Model($this->config, $this->database);
		$form_register = $this->getRegisterForm();
		$form_login    = $this->getLoginForm();

		if ($form_login->validate()) {
			$customer = $model->getModel('core\classes\models\Customer');
			$customer = $customer->get([
				'login' => $form_login->getValue('username'),
			]);
			if ($customer && Encryption::bcrypt_verify($form_login->getValue('password'), $customer->password)) {
				$this->authentication->loginCustomer($customer);
				throw new RedirectException($this->url->getURL('Account'));
			}
			else {
				$form_login->addError('login-failed', 'Login Failed');
			}
		}

		$data = [
			'register' => $form_register,
			'login'    => $form_login,
		];

		$template = new Template($this->config, 'pages/account/login.php', $data);
		$this->response->setContent($template->render());
	}

	public function register() {
		$bcrypt_cost   = $this->config->getSiteParams()->bcrypt_cost;
		$model         = new Model($this->config, $this->database);
		$form_register = $this->getRegisterForm();
		$form_login    = $this->getLoginForm();

		if ($form_register->validate()) {
			$customer = $model->getModel('core\classes\models\Customer');
			$customer->site_id    = $this->config->getSiteParams()->site_id;
			$customer->login      = $form_register->getValue('username');
			$customer->password   = Encryption::bcrypt($form_register->getValue('password1'), $bcrypt_cost);
			$customer->first_name = $form_register->getValue('first-name');
			$customer->last_name  = $form_register->getValue('last-name');
			$customer->email      = $form_register->getValue('email');
			$customer->insert();

			$this->authentication->loginCustomer($customer);
			throw new RedirectException($this->url->getURL('Account'));
		}

		$data = [
			'register' => $form_register,
			'login'    => $form_login,
		];

		$template = new Template($this->config, 'pages/account/login.php', $data);
		$this->response->setContent($template->render());
	}

	protected function getRegisterForm() {
		$model = new Model($this->config, $this->database);

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
			'email' => [
				[
					'type'     => 'function',
					'message'  => 'Email already is already associated with an account',
					'function' => function($value) use ($model) {
						$customer = $model->getModel('core\classes\models\Customer');
						$customer = $customer->get(['email' => $value]);
						if ($customer) {
							return FALSE;
						}
						else {
							return TRUE;
						}
					}
				],
			],
			'username' => [
				[
					'type'     => 'function',
					'message'  => 'Username is taken',
					'function' => function($value) use ($model) {
						$customer = $model->getModel('core\classes\models\Customer');
						$customer = $customer->get(['login' => $value]);
						if ($customer) {
							return FALSE;
						}
						else {
							return TRUE;
						}
					}
				],
			],
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