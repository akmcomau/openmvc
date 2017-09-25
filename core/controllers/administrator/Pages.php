<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;
use core\classes\Page;
use core\classes\Pagination;

class Pages extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
		'add' => ['administrator'],
		'edit' => ['administrator'],
		'delete' => ['administrator'],
	];

	public function index($message = NULL) {
		$this->language->loadLanguageFile('administrator/pages.php');
		$form_search = $this->getPageSearchForm();

		$pagination = new Pagination($this->request, 'url');

		$params = [];
		if ($form_search->validate()) {
			$values = $form_search->getSubmittedValues();
			foreach ($values as $name => $value) {
				if (preg_match('/^search_(.*)$/', $name, $matches) && $value != '') {
					$value = strtolower($value);
					$params[$matches[1]] = $value;
				}
			}
		}

		// default search values
		if (!isset($params['editable'])) $params['editable'] = 'all';

		// get all the pages
		$page  = new Page($this->config, $this->database);
		$pages = $page->getPageList($params, $pagination->getOrdering(), $pagination->getLimitOffset());
		$pagination->setRecordCount(count($page->getPageList($params)));

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
			'pages' => $pages,
			'pagination' => $pagination,
			'form' => $form_search,
			'message_js' => $message_js,
		];

		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel('\core\classes\models\PageCategory');
		$data['categories'] = $page_category->getAsOptions($this->allowedSiteIDs());

		$template = $this->getTemplate('pages/administrator/pages/list.php', $data);
		$this->response->setContent($template->render());
	}

	public function add() {
		$this->language->loadLanguageFile('administrator/pages.php');
		$form_page = $this->getPageForm(TRUE);

		$page  = new Page($this->config, $this->database);
		$data = $page->getPage();

		if ($form_page->validate()) {
			$this->updateFromRequest($form_page, $data);
			$page->update($data, FALSE);
			throw new RedirectException($this->url->getUrl('administrator/Pages', 'index', ['add-success']));
		}
		elseif ($form_page->isSubmitted()) {
			$this->updateFromRequest($form_page, $data);
			$form_page->setNotification('error', $this->language->get('notification_add_error'));
		}

		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel('\core\classes\models\PageCategory');
		$data['categories'] = $page_category->getAsOptions($this->allowedSiteIDs());

		$data['is_add_page'] = TRUE;
		$data['form'] = $form_page;
		$template = $this->getTemplate('pages/administrator/pages/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	public function edit($controller, $method, $sub_page = '-', $action = NULL) {
		$this->language->loadLanguageFile('administrator/pages.php');
		$form_page = $this->getPageForm(FALSE);

		if ($sub_page == '-') $sub_page = NULL;
		if ($sub_page) $method .= '/'.$sub_page;

		$page  = new Page($this->config, $this->database);
		$data = $page->getPage($controller, $method);

		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel('\core\classes\models\PageCategory');
		$data['categories'] = $page_category->getAsOptions($this->allowedSiteIDs());

		if ($action == 'save' && $data['misc_page']) {
			$data['content'] = $_REQUEST['main-content'];
			$page->update($data, TRUE);
			throw new RedirectException($this->url->getUrl('administrator/Pages', 'index', ['update-success']));
		}
		elseif ($form_page->validate()) {
			$this->updateFromRequest($form_page, $data);
			$page->update($data, TRUE);
			throw new RedirectException($this->url->getUrl('administrator/Pages', 'index', ['update-success']));
		}
		elseif ($form_page->isSubmitted()) {
			$this->updateFromRequest($form_page, $data);
			$form_page->setNotification('error', $this->language->get('notification_update_error'));
		}

		if ($data['misc_page']) {
			$template = $this->getTemplate("pages/misc/".$data['method'].".php");
			$data['content'] = $template->getTemplateContent();
		}

		$data['is_add_page'] = FALSE;
		$data['form'] = $form_page;
		$template = $this->getTemplate('pages/administrator/pages/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	public function delete() {
		if ($this->request->requestParam('selected')) {
			$page = new Page($this->config, $this->database);
			foreach ($this->request->requestParam('selected') as $data) {
				list($controller, $method, $sub_method) = explode('-', $data);
				$page->delete($controller, $method, $sub_method);
			}

			throw new RedirectException($this->url->getUrl('administrator/Pages', 'index', ['delete-success']));
		}
	}

	protected function updateFromRequest(FormValidator $form_page, &$data) {
		$data['meta_tags']['title'] = $form_page->getValue('meta_title');
		$data['meta_tags']['description'] = $form_page->getValue('meta_description');
		$data['meta_tags']['keywords'] = $form_page->getValue('meta_keywords');
		$data['meta_tags']['og:image'] = $form_page->getValue('meta_og:image');

		$data['parent_template'] = $form_page->getValue('parent_template');
		$data['banner_image'] = $form_page->getValue('banner_image');

		$data['controller_alias'] = $form_page->getValue('controller_alias');
		if ($form_page->getValue('method_name')) {
			$data['method'] = $form_page->getValue('method_name');
		}
		$data['method_alias'] = $form_page->getValue('method_alias');
		$data['content'] = $form_page->getValue('content');
		$data['link_text'] = $form_page->getValue('link_text');

		$data['category'] = $form_page->getValue('category');
		if ((int)$data['category'] == 0) $data['category'] = NULL;
	}

	protected function getPageSearchForm() {
		$inputs = [
			'search_title' => [
				'type' => 'string',
				'required' => FALSE,
				'max_length' => 256,
				'message' => $this->language->get('error_search_title'),
			],
			'search_url' => [
				'type' => 'string',
				'required' => FALSE,
				'max_length' => 256,
				'message' => $this->language->get('error_search_url'),
			],
			'search_category' => [
				'type' => 'integer',
				'required' => FALSE,
			],
			'search_editable' => [
				'type' => 'string',
				'required' => FALSE,
			],
		];

		return new FormValidator($this->request, 'form-page-search', $inputs);
	}

	protected function getPageForm($is_add_page) {
		$inputs = [
			'meta_title' => [
				'type' => 'string',
				'required' => FALSE,
			],
			'meta_keywords' => [
				'type' => 'string',
				'required' => FALSE,
			],
			'meta_description' => [
				'type' => 'string',
				'required' => FALSE,
			],
			'parent_template' => [
				'type' => 'string',
				'required' => FALSE,
			],
			'banner_image' => [
				'type' => 'string',
				'required' => FALSE,
			],
			'controller_alias' => [
				'type' => 'url-fragment',
				'message' => 'This is required, only A-Z, a-Z, 0-9, dash(-), underscore(_)',
			],
			'method_name' => [
				'type' => 'url-fragment',
				'message' => 'This is required, only A-Z, a-Z, 0-9, dash(-), underscore(_)',
				'required' => $is_add_page,
			],
			'method_alias' => [
				'type' => 'url-fragment',
				'message' => 'This is required, only A-Z, a-Z, 0-9, dash(-), underscore(_)',
			],
			'link_text' => [
				'type' => 'string',
				'required' => FALSE,
			],
			'category' => [
				'type' => 'integer',
				'required' => FALSE,
			],
		];

		return new FormValidator($this->request, 'form-page', $inputs);
	}
}
