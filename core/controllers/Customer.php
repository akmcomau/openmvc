<?php

namespace core\controllers;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;

class Customer extends Controller {

	protected $permissions = [
		'index' => ['customer'],
	];

	public function index() {
		$template = $this->getTemplate('pages/not_implemented.php');
		$this->response->setContent($template->render());
	}

	public function logout() {
		$this->authentication->logout();
		throw new RedirectException($this->url->getURL());
	}

	public function login() {
		if ($this->authentication->customerLoggedIn()) {
			throw new RedirectException($this->url->getURL('Customer'));
		}

		$this->language->loadLanguageFile('customer.php');

		$bcrypt_cost   = $this->config->siteConfig()->bcrypt_cost;
		$model         = new Model($this->config, $this->database);
		$form_login    = $this->getLoginForm();

		if ($form_login->validate()) {
			$customer = $model->getModel('core\classes\models\Customer');
			$customer = $customer->get([
				'login' => $form_login->getValue('username'),
			]);
			if ($customer && Encryption::bcrypt_verify($form_login->getValue('password'), $customer->password)) {
				$this->authentication->loginCustomer($customer);
				throw new RedirectException($this->url->getURL('Customer'));
			}
			else {
				$form_login->addError('login-failed', $this->language->get('login_failed'));
			}
		}

		$data = ['login' => $form_login];

		$template = $this->getTemplate('pages/customer/login.php', $data);
		$this->response->setContent($template->render());
	}

	public function register() {
		$this->language->loadLanguageFile('customer.php');

		$bcrypt_cost   = $this->config->siteConfig()->bcrypt_cost;
		$model         = new Model($this->config, $this->database);
		$form_register = $this->getRegisterForm();

		if ($form_register->validate()) {
			$customer = $model->getModel('core\classes\models\Customer');
			$customer->site_id    = $this->config->siteConfig()->site_id;
			$customer->login      = $form_register->getValue('username');
			$customer->password   = Encryption::bcrypt($form_register->getValue('password1'), $bcrypt_cost);
			$customer->first_name = $form_register->getValue('first-name');
			$customer->last_name  = $form_register->getValue('last-name');
			$customer->email      = $form_register->getValue('email');
			$customer->insert();

			$this->authentication->loginCustomer($customer);
			throw new RedirectException($this->url->getURL('Customer'));
		}

		$data = ['register' => $form_register];

		$template = $this->getTemplate('pages/customer/register.php', $data);
		$this->response->setContent($template->render());
	}

	public function contact_details() {
		$template = $this->getTemplate('pages/not_implemented.php');
		$this->response->setContent($template->render());
	}

	public function change_password() {
		$template = $this->getTemplate('pages/not_implemented.php');
		$this->response->setContent($template->render());
	}

	protected function getRegisterForm() {
		$model = new Model($this->config, $this->database);

		$inputs = [
			'first-name' => [
				'type' => 'string',
				'min_length' => 2,
				'max_length' => 32,
				'message' => $this->language->get('error_first_name'),
			],
			'last-name' => [
				'type' => 'string',
				'min_length' => 2,
				'max_length' => 32,
				'message' => $this->language->get('error_last_name'),
			],
			'email' => [
				'type' => 'email',
				'message' => $this->language->get('error_email')
			],
			'username' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_username')
			],
			'password1' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_password')
			],
			'password2' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_password')
			]
		];

		$validators = [
			'email' => [
				[
					'type'     => 'function',
					'message'  => $this->language->get('error_email_taken'),
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
					'message'  => $this->language->get('error_username_taken'),
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
					'message' => $this->language->get('error_password_mismatch'),
				],
				[
					'type'      => 'regex',
					'regex'     => '\d',
					'modifiers' => '',
					'message'   => $this->language->get('error_password_number'),
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
				'message' => $this->language->get('error_username'),
			],
			'password' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_password'),
			],
		];

		$validators = [
			'password' => [
				[
					'type'      => 'regex',
					'regex'     => '\d',
					'modifiers' => '',
					'message'   => $this->language->get('error_password_number'),
				],
			],
		];

		return new FormValidator($this->request, 'form-login', $inputs, $validators);
	}
}