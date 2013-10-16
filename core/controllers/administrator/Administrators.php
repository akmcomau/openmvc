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
use core\classes\models\Administrator;

class Administrators extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
		'add' => ['administrator'],
		'edit' => ['administrator'],
	];

	public function index() {
		$this->language->loadLanguageFile('administrator/administrators.php');
		$form_search = $this->getAdministratorSearchForm();

		$pagination = new Pagination($this->request, 'username');

		$params = [];
		if ($form_search->validate()) {
			$values = $form_search->getSubmittedValues();
			foreach ($values as $name => $value) {
				if (preg_match('/^search_(email|login)$/', $name, $matches) && $value != '') {
					$value = strtolower($value);
					$params[$matches[1]] = ['type'=>'like', 'value'=>'%'.$value.'%'];
				}
			}
		}

		// get all the administrators
		$model     = new Model($this->config, $this->database);
		$administrator  = $model->getModel('\core\classes\models\Administrator');
		$administrators = $administrator->getMulti($params, $pagination->getOrdering(), $pagination->getLimitOffset());

		$data = [
			'pagination' => $pagination,
			'form' => $form_search,
			'administrators' => $administrators,
		];

		$template = $this->getTemplate('pages/administrator/administrators/list.php', $data);
		$this->response->setContent($template->render());
	}

	public function add() {
		$this->language->loadLanguageFile('administrator/administrators.php');

		$model = new Model($this->config, $this->database);
		$administrator = $model->getModel('\core\classes\models\Administrator');
		$form_administrator = $this->getAdministratorForm(TRUE, $administrator);

		if ($form_administrator->validate()) {
			$this->updateFromRequest($form_administrator, $administrator);
			$administrator->insert();
		}
		elseif ($form_administrator->isSubmitted()) {
			$this->updateFromRequest($form_administrator, $administrator);
		}

		$data = [
			'is_add_page' => TRUE,
			'form' => $form_administrator,
			'administrator' => $administrator,
		];

		$template = $this->getTemplate('pages/administrator/administrators/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	public function edit($administrator_id) {
		$this->language->loadLanguageFile('administrator/administrators.php');

		$model = new Model($this->config, $this->database);
		$administrator = $model->getModel('\core\classes\models\Administrator')->get([
			'id' => (int)$administrator_id,
		]);
		$form_administrator = $this->getAdministratorForm(FALSE, $administrator);

		if ($form_administrator->validate()) {
			$this->updateFromRequest($form_administrator, $administrator);
			$administrator->update();
		}
		elseif ($form_administrator->isSubmitted()) {
			$this->updateFromRequest($form_administrator, $administrator);
		}

		$data = [
			'is_add_page' => FALSE,
			'form' => $form_administrator,
			'administrator' => $administrator,
		];

		$template = $this->getTemplate('pages/administrator/administrators/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	protected function updateFromRequest(FormValidator $form, Administrator $administrator) {
		$administrator->login = $form->getValue('login');
		$administrator->email = $form->getValue('email');
		$administrator->first_name = $form->getValue('first_name');
		$administrator->last_name = $form->getValue('last_name');
		$administrator->phone = $form->getValue('phone');
		$administrator->active = ($form->getValue('active') == 1) ? TRUE : FALSE;

		if ($form->getValue('password1')) {
			$administrator->password = Encryption::bcrypt($form->getValue('password1'), $this->config->siteConfig()->bcrypt_cost);
		}
	}

	protected function getAdministratorSearchForm() {
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

		return new FormValidator($this->request, 'form-administrator-search', $inputs);
	}

	protected function getAdministratorForm($is_add, Administrator $administrator_obj) {
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
					'function' => function($value) use ($model, $administrator_obj) {
						$administrator = $model->getModel('core\classes\models\Administrator');
						$administrator = $administrator->get(['email' => $value]);
						if ($administrator && $administrator->id != $administrator_obj->id) {
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
					'function' => function($value) use ($model, $administrator_obj) {
						$administrator = $model->getModel('core\classes\models\Administrator');
						$administrator = $administrator->get(['login' => $value]);
						if ($administrator && $administrator->id != $administrator_obj->id) {
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

		return new FormValidator($this->request, 'form-administrator', $inputs, $validators);
	}
}