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
	];

	public function index() {
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
		if (!isset($params['editable'])) $params['editable'] = 'editable';

		// get all the pages
		$page  = new Page($this->config, $this->database);
		$pages = $page->getPageList($params, $pagination->getOrdering(), $pagination->getLimitOffset());
		$pagination->setRecordCount(count($page->getPageList()));

		$data = [
			'pages' => $pages,
			'pagination' => $pagination,
			'form' => $form_search,
		];

		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel('\core\classes\models\PageCategory');
		$data['categories'] = $page_category->getAsOptions();

		$template = $this->getTemplate('pages/administrator/pages/list.php', $data);
		$this->response->setContent($template->render());
	}

	public function add() {
		$this->language->loadLanguageFile('administrator/pages.php');
		$form_page = $this->getPageForm();

		$page  = new Page($this->config, $this->database);
		$data = $page->getPage();

		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel('\core\classes\models\PageCategory');
		$data['categories'] = $page_category->getAsOptions();

		if ($form_page->validate()) {
			$this->updateFromRequest($form_page, $data);
			$page->update($data, FALSE);
		}
		elseif ($form_page->isSubmitted()) {
			$this->updateFromRequest($form_page, $data);
		}

		$data['is_add_page'] = TRUE;
		$data['form'] = $form_page;
		$template = $this->getTemplate('pages/administrator/pages/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	public function edit($controller, $method, $sub_page = NULL) {
		$this->language->loadLanguageFile('administrator/pages.php');
		$form_page = $this->getPageForm();

		if ($sub_page) $method .= '/'.$sub_page;

		$page  = new Page($this->config, $this->database);
		$data = $page->getPage($controller, $method);

		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel('\core\classes\models\PageCategory');
		$data['categories'] = $page_category->getAsOptions();

		if ($form_page->validate()) {
			$this->updateFromRequest($form_page, $data);
			$page->update($data, TRUE);
		}
		elseif ($form_page->isSubmitted()) {
			$this->updateFromRequest($form_page, $data);
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

	protected function updateFromRequest(FormValidator $form_page, &$data) {
		$data['meta_tags']['title'] = $form_page->getValue('meta_title');
		$data['meta_tags']['description'] = $form_page->getValue('meta_description');
		$data['meta_tags']['keywords'] = $form_page->getValue('meta_keywords');
		$data['controller_alias'] = $form_page->getValue('controller_alias');
		if ($form_page->getValue('page_method')) {
			$data['method'] = $form_page->getValue('page_method');
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

	protected function getPageForm() {
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
			'controller_alias' => [
				'type' => 'url-fragment',
				'message' => 'This is required, only A-Z, a-Z, 0-9, dash(-), underscore(_)',
			],
			'method' => [
				'type' => 'url-fragment',
				'message' => 'This is required, only A-Z, a-Z, 0-9, dash(-), underscore(_)',
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