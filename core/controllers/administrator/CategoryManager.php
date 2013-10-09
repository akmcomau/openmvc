<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;

class CategoryManager extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
	];

	public function index($type) {
		switch ($type) {
			case 'product':
				$this->product();
				break;

			default:
				$template = $this->getTemplate('pages/not_implemented.php');
				$this->response->setContent($template->render());
				break;
		}
	}

	public function page() {
		$this->category_manager('\core\classes\models\PageCategory');
	}

	public function block() {
		$this->category_manager('\core\classes\models\BlockCategory');
	}

	protected function category_manager($model_class) {
		$this->language->loadLanguageFile('administrator/category_manager.php');

		$form_category = $this->getAddEditForm();
		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel($model_class);

		$this->addCategory($page_category, $form_category);

		$category_id = NULL;
		if ((int)$this->request->requestParam('category')) {
			$category_id = (int)$this->request->requestParam('category');
		}
		$categ_data = $page_category->getAllByParent();

		foreach ($categ_data as $parent_id => &$categories) {
			foreach ($categories as &$category) {
				$category['children'] = 0;
				if (isset($categ_data[$category['id']])) {
					$category['children'] = count($categ_data[$category['id']]);
				}
			}
		}

		$data = [
			'categories' => isset($categ_data[$category_id]) ? $categ_data[$category_id] : [],
			'form' => $form_category,
		];

		$template = $this->getTemplate('pages/administrator/category_manager.php', $data);
		$this->response->setContent($template->render());
	}

	protected function addCategory(Model $model, FormValidator $form) {
		if ($form->validate()) {
			if ((int)$this->request->requestParam('category')) {
				$model->parent_id = (int)$this->request->requestParam('category');
			}

			$model->site_id = $this->config->siteConfig()->site_id;
			$model->name = $form->getValue('name');
			$model->insert();
		}
	}

	protected function getAddEditForm() {
		$inputs = [
			'name' => [
				'type' => 'string',
				'min_length' => 1,
				'required' => true,
				'message' => $this->language->get('error_category'),
			],
		];
		return new FormValidator($this->request, 'form-category', $inputs);
	}
}