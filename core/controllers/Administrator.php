<?php

namespace core\controllers;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;

class Administrator extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
	];

	public function index() {
	}

	public function logout() {
		$this->authentication->logoutAdministrator();
		throw new RedirectException($this->url->getURL());
	}

	public function login_register() {
		$this->login();
	}

	public function login() {
		$this->language->loadLanguageFile('administrator.php');

		$bcrypt_cost   = $this->config->siteConfig()->bcrypt_cost;
		$model         = new Model($this->config, $this->database);
		$form_login    = $this->getLoginForm();

		if ($form_login->validate()) {
			$admin = $model->getModel('core\classes\models\Administrator');
			$admin = $admin->get([
				'login' => $form_login->getValue('username'),
			]);
			if ($admin && Encryption::bcrypt_verify($form_login->getValue('password'), $admin->password)) {
				$this->authentication->loginAdministrator($admin);
				throw new RedirectException($this->url->getURL('Administrator'));
			}
			else {
				$form_login->addError('login-failed', $this->language->get('login_failed'));
			}
		}

		$data = ['login' => $form_login];

		$template = $this->getTemplate('pages/administrator/login.php', $data);
		$this->response->setContent($template->render());
	}

	public function account_details() {
		$template = $this->getTemplate('pages/not_implemented.php');
		$this->response->setContent($template->render());
	}

	public function change_password() {
		$template = $this->getTemplate('pages/not_implemented.php');
		$this->response->setContent($template->render());
	}

	public function error_401() {
		$this->show_admin_layout = TRUE;
		$this->language->loadLanguageFile('error.php');
		header("HTTP/1.1 401 Permission Denied");
		$template = $this->getTemplate('pages/error_401.php');
		$this->response->setContent($template->render());
	}

	public function error_404() {
		$this->show_admin_layout = TRUE;
		$this->language->loadLanguageFile('error.php');
		header("HTTP/1.1 404 Not Found");
		$template = $this->getTemplate('pages/error_404.php');
		$this->response->setContent($template->render());
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