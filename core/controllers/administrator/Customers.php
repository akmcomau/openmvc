<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\Pagination;
use core\classes\renderable\Controller;
use core\classes\Model;
use core\classes\models\Customer;

class Customers extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
		'add' => ['administrator'],
		'edit' => ['administrator'],
		'delete' => ['administrator'],
	];

	public function index($message = NULL) {
		if ($this->config->database->engine == 'none') {
			$template = $this->getTemplate('pages/administrator/database_required.php');
			$this->response->setContent($template->render());
			return;
		}

		$this->language->loadLanguageFile('administrator/customers.php');
		$form_search = $this->getCustomerSearchForm();

		$pagination = new Pagination($this->request, 'username');

		$params = ['site_id' => ['type'=>'in', 'value'=>$this->allowedSiteIDs()]];
		if ($form_search->validate()) {
			$values = $form_search->getSubmittedValues();
			foreach ($values as $name => $value) {
				if (preg_match('/^search_(email|login)$/', $name, $matches) && $value != '') {
					$value = strtolower($value);
					$params[$matches[1]] = ['type'=>'like', 'value'=>'%'.$value.'%'];
				}
			}
		}

		// get all the customers
		$model     = new Model($this->config, $this->database);
		$customer  = $model->getModel('\core\classes\models\Customer');
		$customers = $customer->getMulti($params, $pagination->getOrdering(), $pagination->getLimitOffset());
		$pagination->setRecordCount($customer->getCount($params));

		$message_js = NULL;
		switch($message) {
			case 'delete-success':
				$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_delete_success')).'");';
				break;

			case 'add-success':
				$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_add_success')).'");';
				break;

			case 'update-success':
				$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_update_success')).'");';
				break;
		}

		$data = [
			'pagination' => $pagination,
			'form' => $form_search,
			'customers' => $customers,
			'message_js' => $message_js,
		];

		$template = $this->getTemplate('pages/administrator/customers/list.php', $data);
		$this->response->setContent($template->render());
	}

	public function add() {
		if ($this->config->database->engine == 'none') {
			$template = $this->getTemplate('pages/administrator/database_required.php');
			$this->response->setContent($template->render());
			return;
		}

		$this->language->loadLanguageFile('administrator/customers.php');

		$model = new Model($this->config, $this->database);
		$customer = $model->getModel('\core\classes\models\Customer');
		$customer->customer_email_verified = FALSE;
		$customer->site_id = $this->config->siteConfig()->site_id;
		$form_customer = $this->getCustomerForm(TRUE, $customer);

		if ($form_customer->validate()) {
			$this->updateFromRequest($form_customer, $customer);
			$customer->insert();
			$form_customer->setNotification('success', $this->language->get('notification_add_success'));
			throw new RedirectException($this->url->getUrl('administrator/Customers', 'index', ['add-success']));
		}
		elseif ($form_customer->isSubmitted()) {
			$this->updateFromRequest($form_customer, $customer);
			$form_customer->setNotification('error', $this->language->get('notification_add_error'));
		}

		$data = [
			'is_add_page' => TRUE,
			'form' => $form_customer,
			'customer' => $customer,
		];

		$template = $this->getTemplate('pages/administrator/customers/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	public function edit($customer_id) {
		$this->language->loadLanguageFile('administrator/customers.php');

		$model = new Model($this->config, $this->database);
		$customer = $model->getModel('\core\classes\models\Customer')->get([
			'id' => (int)$customer_id,
		]);
		$this->siteProtection($customer);

		$form_customer = $this->getCustomerForm(FALSE, $customer);
		if ($form_customer->validate()) {
			$this->updateFromRequest($form_customer, $customer);
			$customer->update();
			throw new RedirectException($this->url->getUrl('administrator/Customers', 'index', ['update-success']));
		}
		elseif ($form_customer->isSubmitted()) {
			$this->updateFromRequest($form_customer, $customer);
			$form_customer->setNotification('error', $this->language->get('notification_update_error'));
		}

		$data = [
			'is_add_page' => FALSE,
			'form' => $form_customer,
			'customer' => $customer,
		];

		$template = $this->getTemplate('pages/administrator/customers/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	public function delete() {
		if ($this->request->requestParam('selected')) {
			$model = new Model($this->config, $this->database);
			$customer_model = $model->getModel('\core\classes\models\Customer');
			foreach ($this->request->requestParam('selected') as $id) {
				$customer = $customer_model->get(['id' => $id]);
				$this->siteProtection($customer);
				$customer->delete();
			}

			throw new RedirectException($this->url->getUrl('administrator/Customers', 'index', ['delete-success']));
		}
	}

	protected function updateFromRequest(FormValidator $form, Customer $customer) {
		$customer->login = $form->getValue('login');
		$customer->email = $form->getValue('email');
		$customer->first_name = $form->getValue('first_name');
		$customer->last_name = $form->getValue('last_name');
		$customer->phone = $form->getValue('phone');
		$customer->active = ($form->getValue('active') == 1) ? TRUE : FALSE;

		if ($form->getValue('password1')) {
			$customer->password = Encryption::bcrypt($form->getValue('password1'), $this->config->siteConfig()->bcrypt_cost);
		}
	}

	protected function getCustomerSearchForm() {
		$inputs = [
			'search_email' => [
				'type' => 'string',
				'required' => FALSE,
				'max_length' => 256,
				'message' => $this->language->get('error_search_email'),
			],
			'search_login' => [
				'type' => 'string',
				'required' => FALSE,
				'max_length' => 32,
				'message' => $this->language->get('error_search_login'),
			],
		];

		return new FormValidator($this->request, 'form-customer-search', $inputs);
	}

	protected function getCustomerForm($is_add, Customer $customer_obj) {
		$model = new Model($this->config, $this->database);

		$inputs = [
			'login' => [
				'type' => 'string',
				'required' => TRUE,
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_login'),
			],
			'email' => [
				'type' => 'email',
				'required' => TRUE,
				'message' => $this->language->get('error_email'),
			],
			'first_name' => [
				'type' => 'string',
				'required' => TRUE,
				'max_length' => 128,
				'message' => $this->language->get('error_first_name'),
			],
			'last_name' => [
				'type' => 'string',
				'required' => TRUE,
				'max_length' => 128,
				'message' => $this->language->get('error_last_name'),
			],
			'phone' => [
				'type' => 'string',
				'required' => FALSE,
				'max_length' => 32,
				'message' => $this->language->get('error_phone'),
			],
			'active' => [
				'type' => 'integer',
				'required' => TRUE,
			],
			'password1' => [
				'type' => 'string',
				'required' => $is_add,
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_password_format'),
			],
			'password2' => [
				'type' => 'string',
				'required' => $is_add,
				'min_length' => 6,
				'max_length' => 32,
				'message' => $this->language->get('error_password_format'),
			],
		];

		$validators = [
			'email' => [
				[
					'type'     => 'function',
					'message'  => $this->language->get('error_email_taken'),
					'function' => function($value) use ($model, $customer_obj) {
						$customer = $model->getModel('core\classes\models\Customer');
						$customer = $customer->get(['email' => $value]);
						if ($customer && $customer->id != $customer_obj->id) {
							return FALSE;
						}
						else {
							return TRUE;
						}
					}
				],
			],
			'login' => [
				[
					'type'     => 'function',
					'message'  => $this->language->get('error_login_taken'),
					'function' => function($value) use ($model, $customer_obj) {
						$customer = $model->getModel('core\classes\models\Customer');
						$customer = $customer->get(['login' => $value]);
						if ($customer && $customer->id != $customer_obj->id) {
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
					'message'   => $this->language->get('error_password_format'),
				],
			],
		];

		return new FormValidator($this->request, 'form-customer', $inputs, $validators);
	}
}