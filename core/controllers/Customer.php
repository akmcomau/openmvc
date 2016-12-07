<?php

namespace core\controllers;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Email;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;
use core\classes\models\Customer as CustomerModel;

class Customer extends Controller {

	protected $permissions = [
		'index' => ['customer'],
		'change_password' => ['customer'],
		'contact_details' => ['customer'],
	];

	protected $customer_class = 'core\classes\models\Customer';

	public function getAllUrls($include_filter = NULL, $exclude_filter = NULL) {
		$controller = $this->url->getControllerClassName('\\'.get_class($this));
		$urls = [
			['url' => $this->url->getUrl($controller, 'register')],
			['url' => $this->url->getUrl($controller, 'login')],
			['url' => $this->url->getUrl($controller, 'login_register')],
			['url' => $this->url->getUrl($controller, 'forgot')],
		];
		return $urls;
	}

	public function index() {
		$template = $this->getTemplate('pages/not_implemented.php');
		$this->response->setContent($template->render());
	}

	public function logout() {
		$this->logger->info('Logout Customer');
		$this->authentication->logoutCustomer();
		throw new RedirectException($this->url->getUrl());
	}

	public function login_register($controller = NULL, $method = NULL, $params = NULL) {
		$this->language->loadLanguageFile('customer.php');
		$form_login    = $this->getLoginForm();
		$form_register = $this->getRegisterForm();

		// get the remember me token
		$remember_me = NULL;
		if (isset($this->request->cookies['rememberme'])) {
			$remember_me = json_decode($this->request->cookies['rememberme']);
		}

		$data = [
			'login' => $form_login,
			'register' => $form_register,
			'controller' => $controller,
			'method' => $method,
			'params' => $params,
			'remember_me' => $remember_me,
		];

		$template = $this->getTemplate('pages/customer/login_register.php', $data);
		$this->response->setContent($template->render());
	}

	protected function getCustomerLookupForLogin($form_login) {
		return [
			'login' => $form_login->getValue('username'),
			'active' => TRUE,
		];
	}

	protected function rememberMeCookie($customer) {
		$expire = time() + 365 * 24 * 60 * 60;
		$token = $customer->getRememberMeToken();
		$details = json_encode(['login' => $customer->login, 'password' => $token]);
		setcookie('rememberme', $details, $expire, '/', $this->config->getSiteDomain());
	}

	public function login($controller = NULL, $method = NULL, $param = NULL) {
		if ($this->authentication->customerLoggedIn()) {
			throw new RedirectException($this->url->getUrl('Customer'));
		}
		$params = NULL;
		if ($param) {
			$params = [ $param ];
		}

		$this->language->loadLanguageFile('customer.php');

		$bcrypt_cost   = $this->config->siteConfig()->bcrypt_cost;
		$model         = new Model($this->config, $this->database);
		$form_login    = $this->getLoginForm();
		$form_register = $this->getRegisterForm();

		if ($form_login->validate()) {
			$customer = $model->getModel($this->customer_class);
			$customer = $customer->get($this->getCustomerLookupForLogin($form_login));
			if ($customer && (
				Encryption::bcrypt_verify($form_login->getValue('password'), $customer->password) ||
				$customer->remember_me == $form_login->getValue('password')
			)) {
				$this->logger->info('Login Customer: '.$customer->id);
				$this->authentication->loginCustomer($customer);

				// save the remember me cookie
				if ($this->request->postParam('remember_me')) {
					$this->rememberMeCookie($customer);
				}
				else {
					setcookie('rememberme', NULL, -1, '/', $this->config->getSiteDomain());
				}

				// save the controller for login redirect
				if ($this->config->siteConfig()->post_login_redirect) {
					$this->request->session->delete('login-redirect-done');
					$this->request->session->set('login-redirect-controller', $controller);
					$this->request->session->set('login-redirect-method', $method);
					$this->request->session->set('login-redirect-params', $params);
				}

				if ($this->config->siteConfig()->post_login_redirect) {
					throw new RedirectException($this->url->getUrl('Customer', 'loginRedirect'));
				}
				else if ($controller) {
					throw new RedirectException($this->url->getUrl($controller, $method, $params));
				}
				else {
					throw new RedirectException($this->url->getUrl('Customer'));
				}
			}
			else {
				$this->logger->info('Login Failed for Customer: '.($customer ? $customer->id : 'Invalid login'));
				$form_login->addError('login-failed', $this->language->get('login_failed'));
			}
		}

		// get the remember me token
		$remember_me = NULL;
		if (isset($this->request->cookies['rememberme'])) {
			$remember_me = json_decode($this->request->cookies['rememberme']);
		}

		$data = [
			'login' => $form_login,
			'register' => $form_register,
			'controller' => $controller,
			'method' => $method,
			'params' => $param,
			'remember_me' => $remember_me,
		];

		$template = $this->getTemplate('pages/customer/login.php', $data);
		$this->response->setContent($template->render());
	}

	public function loginRedirect() {
		$controller = $this->request->session->get('login-redirect-controller');
		$method = $this->request->session->get('login-redirect-method');
		$params = $this->request->session->get('login-redirect-params');

		$url = $this->url->getUrl('Customer');
		if ($controller) {
			$url = $this->url->getUrl($controller, $method, $params);
		}

		$data = ['url' => $url];
		$template = $this->getTemplate('pages/customer/login_redirect.php', $data);
		$this->response->setContent($template->render());
	}

	public function register($controller = NULL, $method = NULL, $params = NULL) {
		$this->language->loadLanguageFile('customer.php');

		$bcrypt_cost   = $this->config->siteConfig()->bcrypt_cost;
		$model         = new Model($this->config, $this->database);
		$form_register = $this->getRegisterForm();

		if ($form_register->validate()) {
			$customer = $model->getModel($this->customer_class);
			$customer->site_id    = $this->config->siteConfig()->site_id;
			$customer->login      = $form_register->getValue('username');
			$customer->password   = Encryption::bcrypt($form_register->getValue('password1'), $bcrypt_cost);
			$customer->first_name = $form_register->getValue('first-name');
			$customer->last_name  = $form_register->getValue('last-name');
			$customer->email      = $form_register->getValue('email');
			$customer->insert();

			$this->logger->info('New Customer Registration: '.$customer->id);

			$data = [
				'name' => $customer->getName(),
				'username' => $customer->login,
			];
			$body = $this->getTemplate('emails/account_created.txt.php', $data);
			$html = $this->getTemplate('emails/account_created.html.php', $data);
			$email = new Email($this->config);
			$email->setToEmail($customer->email);
			$email->setSubject($this->language->get('account_created'));
			$email->setBodyTemplate($body);
			$email->setHtmlTemplate($html);
			$email->send();

			$this->authentication->loginCustomer($customer);

			if ($controller) {
				throw new RedirectException($this->url->getUrl($controller, $method, $params));
			}
			else {
				throw new RedirectException($this->url->getUrl('Customer', 'index', ['registered']));
			}
		}

		$data = [
			'register' => $form_register,
			'controller' => $controller,
			'method' => $method,
			'params' => $params,
		];

		$template = $this->getTemplate('pages/customer/register.php', $data);
		$this->response->setContent($template->render());
	}

	public function forgot() {
		$this->language->loadLanguageFile('customer.php');

		$message_js = NULL;
		if ($this->request->requestParam('email')) {
			$model       = new Model($this->config, $this->database);
			$customer    = $model->getModel($this->customer_class)->get([
				'email' => ['type' => 'lower=', 'value' => $this->request->requestParam('email')],
			]);

			if ($customer) {
				if ($this->request->requestParam('forgot-username')) {
					$data = [
						'name' => $customer->getName(),
						'username' => $customer->login,
						'customer' => $customer,
					];
					$body = $this->getTemplate('emails/forgot_username.txt.php', $data);
					$html = $this->getTemplate('emails/forgot_username.html.php', $data);
					$email = new Email($this->config);
					$email->setToEmail($customer->email);
					$email->setSubject($this->config->siteConfig()->name.': '.$this->language->get('forgot_username_subject'));
					$email->setBodyTemplate($body);
					$email->setHtmlTemplate($html);
					$email->send();

					$this->logger->info('Forgot Username for Customer: '.$customer->id);

					$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('forgot_username_email_sent')).'");';
				}
				elseif ($this->request->requestParam('forgot-password')) {
					$token = $customer->generateToken();
					$enc_customer_id = Encryption::obfuscate($customer->id, $this->config->siteConfig()->secret);
					$data = [
						'url' => $this->url->getUrl('Customer', 'reset', [$enc_customer_id, $token]),
						'name' => $customer->getName(),
						'username' => $customer->login,
						'customer' => $customer,
					];
					$body = $this->getTemplate('emails/forgot_password.txt.php', $data);
					$html = $this->getTemplate('emails/forgot_password.html.php', $data);
					$email = new Email($this->config);
					$email->setToEmail($customer->email);
					$email->setSubject($this->config->siteConfig()->name.': '.$this->language->get('forgot_password_subject'));
					$email->setBodyTemplate($body);
					$email->setHtmlTemplate($html);
					$email->send();

					$this->logger->info('Forgot Password for Customer: '.$customer->id);

					$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('forgot_password_email_sent')).'");';
				}
			}
			else {
				$message_js = 'FormValidator.displayPageNotification("error", "'.htmlspecialchars($this->language->get('email_not_found')).'");';
			}
		}
		$data = [
			'message_js' => $message_js,
		];

		$template = $this->getTemplate('pages/customer/forgot.php', $data);
		$this->response->setContent($template->render());
	}

	public function reset($customer_id, $token) {
		$customer_id = Encryption::defuscate($customer_id, $this->config->siteConfig()->secret);
		$model = new Model($this->config, $this->database);
		$customer = $model->getModel($this->customer_class)->get([
			'id' => $customer_id,
			'token' => $token,
		]);
		if (!$customer) {
			throw new RedirectException($this->url->getUrl('Customer', 'login', ['invalid_token']));
		}

		$this->logger->info('Password Reset for Customer: '.$customer->id);

		// login the customer
		$this->authentication->loginCustomer($customer);
		$this->authentication->forcePasswordChange(TRUE);
		throw new RedirectException($this->url->getUrl('Customer', 'change_password'));
	}

	public function contact_details($status = NULL) {
		$this->language->loadLanguageFile('customer.php');

		$model       = new Model($this->config, $this->database);
		$customer_id = $this->getAuthentication()->getCustomerID();
		$customer    = $model->getModel($this->customer_class)->get(['id' => $customer_id]);
		$form = $this->getDetailsForm($customer);

		if ($form->validate()) {
			$customer->first_name = $form->getValue('first_name');
			$customer->last_name = $form->getValue('last_name');
			$customer->email = $form->getValue('email');
			$customer->phone = $form->getValue('phone');
			$customer->update();

			$this->logger->info('Update contact details for Customer: '.$customer->id);

			throw new RedirectException($this->url->getUrl('Customer', 'contact_details', ['update-success']));
		}
		elseif ($form->isSubmitted()) {
			$customer->first_name = $form->getValue('first_name');
			$customer->last_name = $form->getValue('last_name');
			$customer->email = $form->getValue('email');
			$customer->phone = $form->getValue('phone');
		}

		$message_js = NULL;
		if ($status == 'update-success') {
			$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('update_details_success')).'");';
		}

		$data = [
			'form' => $form,
			'message_js' => $message_js,
			'customer' => $customer,
		];

		$template = $this->getTemplate('pages/customer/update_details.php', $data);
		$this->response->setContent($template->render());
	}

	public function change_password($status = NULL) {
		$this->language->loadLanguageFile('customer.php');

		$bcrypt_cost = $this->config->siteConfig()->bcrypt_cost;
		$model       = new Model($this->config, $this->database);
		$customer_id = $this->getAuthentication()->getCustomerID();
		$customer    = $model->getModel($this->customer_class)->get(['id' => $customer_id]);
		$form = $this->getPasswordForm($customer);

		if ($form->validate()) {
			$customer->password = Encryption::bcrypt($form->getValue('password1'), $bcrypt_cost);
			$customer->update();
			$this->authentication->forcePasswordChange(FALSE);

			$this->logger->info('Change password for Customer: '.$customer->id);

			throw new RedirectException($this->url->getUrl('Customer', 'change_password', ['update-success']));
		}

		$message_js = NULL;
		if ($status == 'update-success') {
			$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('change_password_success')).'");';
		}

		$data = [
			'form' => $form,
			'message_js' => $message_js,
			'force_password_change' => $this->authentication->forcePasswordChangeEnabled(),
		];

		$template = $this->getTemplate('pages/customer/change_password.php', $data);
		$this->response->setContent($template->render());
	}

	protected function getDetailsForm(CustomerModel $customer) {
		$inputs = [
			'first_name' => [
				'type' => 'string',
				'min_length' => 2,
				'max_length' => 32,
				'message' => $this->language->get('error_first_name'),
			],
			'last_name' => [
				'type' => 'string',
				'min_length' => 2,
				'max_length' => 32,
				'message' => $this->language->get('error_last_name'),
			],
			'email' => [
				'type' => 'email',
				'message' => $this->language->get('error_email')
			],
			'phone' => [
				'type' => 'string',
				'message' => $this->language->get('error_phone'),
				'required' => FALSE,
			],
		];

		$validators = [
			'email' => [
				[
					'type'     => 'function',
					'message'  => $this->language->get('error_email_taken'),
					'function' => function($value) use ($customer) {
						$check = $customer->get(['email' => $value, 'active' => TRUE]);
						if (!$check || ($check && $check->id == $customer->id)) {
							return TRUE;
						}
						else {
							return FALSE;
						}
					}
				],
			],
			'phone' => [
				[
					'type'     => 'function',
					'message'  => $this->language->get('error_phone'),
					'function' => function($value) {
						if (preg_match('/[^0-9+()-]/', $value)) {
							return FALSE;
						}
						else {
							return TRUE;
						}
					}
				],
			],
		];

		return new FormValidator($this->request, 'form-update-details', $inputs, $validators);
	}

	protected function getPasswordForm(CustomerModel $customer) {
		$inputs = [
			'current_password' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_password'),
				'required' => !$this->getAuthentication()->forcePasswordChangeEnabled(),
			],
			'password1' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_password'),
			],
			'password2' => [
				'type' => 'string',
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_password'),
			],
		];

		$validators = [
			'current_password' => [
				[
					'type'     => 'function',
					'message'  => $this->language->get('error_current_password'),
					'function' => function($value) use ($customer) {
						if ($this->getAuthentication()->forcePasswordChangeEnabled()) {
							return TRUE;
						}
						if (Encryption::bcrypt_verify($value, $customer->password)) {
							return TRUE;
						}
						else {
							return FALSE;
						}
					},
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

		return new FormValidator($this->request, 'form-change-password', $inputs, $validators);
	}

	public function getRegisterForm() {
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

		$customer_class = $this->customer_class;
		$validators = [
			'email' => [
				[
					'type'     => 'function',
					'message'  => $this->language->get('error_email_taken'),
					'function' => function($value) use ($model, $customer_class) {
						$customer = $model->getModel($customer_class);
						$customer = $customer->get(['email' => $value, 'active' => TRUE]);
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
					'function' => function($value) use ($model, $customer_class) {
						$customer = $model->getModel($customer_class);
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

	public function getLoginForm() {
		$inputs = [
			'username' => [
				'type' => 'string',
				'message' => $this->language->get('error_username'),
			],
			'password' => [
				'type' => 'string',
				'message' => $this->language->get('error_password'),
			],
		];

		return new FormValidator($this->request, 'form-login', $inputs);
	}
}
