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

	public function index() {
		$template = $this->getTemplate('pages/not_implemented.php');
		$this->response->setContent($template->render());
	}

	public function logout() {
		$this->authentication->logoutCustomer();
		throw new RedirectException($this->url->getURL());
	}

	public function login_register($controller = NULL, $method = NULL, $params = NULL) {
		$this->language->loadLanguageFile('customer.php');
		$form_login    = $this->getLoginForm();
		$form_register = $this->getRegisterForm();

		$data = [
			'login' => $form_login,
			'register' => $form_register,
			'controller' => $controller,
			'method' => $method,
			'params' => $params,
		];

		$template = $this->getTemplate('pages/customer/login_register.php', $data);
		$this->response->setContent($template->render());

	}

	public function login($controller = NULL, $method = NULL, $params = NULL) {
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

				if ($controller) {
					throw new RedirectException($this->url->getURL($controller, $method, $params));
				}
				else {
					throw new RedirectException($this->url->getURL('Customer'));
				}
			}
			else {
				$form_login->addError('login-failed', $this->language->get('login_failed'));
			}
		}

		$data = [
			'login' => $form_login,
			'controller' => $controller,
			'method' => $method,
			'params' => $params,
		];

		$template = $this->getTemplate('pages/customer/login.php', $data);
		$this->response->setContent($template->render());
	}

	public function register($controller = NULL, $method = NULL, $params = NULL) {
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

			if ($controller) {
				throw new RedirectException($this->url->getURL($controller, $method, $params));
			}
			else {
				throw new RedirectException($this->url->getURL('Customer'));
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
			$customer    = $model->getModel('\core\classes\models\Customer')->get([
				'email' => $this->request->requestParam('email'),
			]);

			if ($customer) {
				if ($this->request->requestParam('forgot-username')) {
					$data = [
						'name' => $customer->getName(),
						'username' => $customer->login,
					];
					$body = $this->getTemplate('emails/forgot_username.txt.php', $data);
					$html = $this->getTemplate('emails/forgot_username.html.php', $data);
					$email = new Email($this->config);
					$email->setToEmail($customer->email);
					$email->setSubject($this->config->siteConfig()->name.': '.$this->language->get('forgot_username_subject'));
					$email->setBodyTemplate($body);
					$email->setHtmlTemplate($html);
					$email->send();

					$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('forgot_username_email_sent')).'");';
				}
				elseif ($this->request->requestParam('forgot-password')) {
					$token = $customer->generateToken();
					$enc_customer_id = Encryption::obfuscate($customer->id, $this->config->siteConfig()->secret);
					$data = [
						'url' => $this->config->getUrl().$this->url->getURL('Customer', 'reset', [$enc_customer_id, $token]),
						'name' => $customer->getName(),
						'username' => $customer->login,
					];
					$body = $this->getTemplate('emails/forgot_password.txt.php', $data);
					$html = $this->getTemplate('emails/forgot_password.html.php', $data);
					$email = new Email($this->config);
					$email->setToEmail($customer->email);
					$email->setSubject($this->config->siteConfig()->name.': '.$this->language->get('forgot_password_subject'));
					$email->setBodyTemplate($body);
					$email->setHtmlTemplate($html);
					$email->send();

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
		$customer = $model->getModel('\core\classes\models\Customer')->get([
			'id' => $customer_id,
			'token' => $token,
		]);
		if (!$customer) {
			throw new RedirectException($this->url->getURL('Customer', 'login'. ['invalid_token']));
		}

		// login the customer
		$this->authentication->loginCustomer($customer);
		$this->authentication->forcePasswordChange(TRUE);
		throw new RedirectException($this->url->getURL('Customer', 'change_password'));
	}

	public function contact_details($status = NULL) {
		$this->language->loadLanguageFile('customer.php');

		$model       = new Model($this->config, $this->database);
		$customer_id = $this->getAuthentication()->getCustomerID();
		$customer    = $model->getModel('\core\classes\models\Customer')->get(['id' => $customer_id]);
		$form = $this->getDetailsForm($customer);

		if ($form->validate()) {
			$customer->first_name = $form->getValue('first_name');
			$customer->last_name = $form->getValue('last_name');
			$customer->email = $form->getValue('email');
			$customer->phone = $form->getValue('phone');
			$customer->update();
			throw new RedirectException($this->url->getURL('Customer', 'contact_details', ['update-success']));
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
		$customer    = $model->getModel('\core\classes\models\Customer')->get(['id' => $customer_id]);
		$form = $this->getPasswordForm($customer);

		if ($form->validate()) {
			$customer->password = Encryption::bcrypt($form->getValue('password1'), $bcrypt_cost);
			$customer->update();
			$this->authentication->forcePasswordChange(FALSE);
			throw new RedirectException($this->url->getURL('Customer', 'change_password', ['update-success']));
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
						$check = $customer->get(['email' => $value]);
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