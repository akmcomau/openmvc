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

	public function getAllUrls($include_filter = NULL, $exclude_filter = NULL) {
		return [];
	}

	public function index() {
	}

	public function logout() {
		$this->logger->info('Logout Administrator');
		$this->authentication->logoutAdministrator();
		throw new RedirectException($this->url->getUrl());
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
			// use the config file to look up the admin user
			if ($this->config->database->engine == 'none') {
				$site = $this->config->siteConfig();
				$theme = $site->theme;

				$root_path = __DIR__.DS.'..'.DS.'..'.DS;
				$default_path = 'core'.DS.'config'.DS;
				$default_file = $root_path.$default_path.'admin_users.php';
				$theme_path = 'sites'.DS.$site->namespace.DS.'config'.DS;
				$theme_file = $root_path.$theme_path.'admin_users.php';

				$admin = NULL;
				if (file_exists($theme_file) || file_exists($default_file)) {
					if (file_exists($theme_file)) {
						require($theme_file);
					}
					elseif (file_exists($default_file)) {
						require($default_file);
					}
					if (isset($_ADMIN_USERS[$form_login->getValue('username')])) {
						$record = $_ADMIN_USERS[$form_login->getValue('username')];
						$admin = $model->getModel('core\classes\models\Administrator', $record);
					}
				}
			}
			// look up the admin user in the database
			else {
				$admin = $model->getModel('core\classes\models\Administrator');
				$admin = $admin->get([
					'login' => $form_login->getValue('username'),
				]);
			}

			if ($admin && Encryption::bcrypt_verify($form_login->getValue('password'), $admin->password)) {
				$this->logger->info('Login Administrator: '.$admin->id);
				$this->authentication->loginAdministrator($admin);
				throw new RedirectException($this->url->getUrl('Administrator'));
			}
			else {
				$this->logger->info('Login Failed for Administrator: '.($admin ? $admin->id : 'Invalid login'));
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

	public function error401() {
		$this->show_admin_layout = TRUE;
		$this->language->loadLanguageFile('error.php');
		header("HTTP/1.1 401 Permission Denied");
		$template = $this->getTemplate('pages/error_401.php');
		$this->response->setContent($template->render());
	}

	public function error404() {
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
			],
			'password' => [
				'type' => 'string',
			],
		];

		return new FormValidator($this->request, 'form-login', $inputs);
	}
}