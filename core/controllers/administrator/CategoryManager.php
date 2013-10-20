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
		'block' => ['administrator'],
		'page' => ['administrator'],
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

	protected function category_manager($model_class, $allow_subcategories = TRUE, $readonly = FALSE) {
		$this->language->loadLanguageFile('administrator/category_manager.php');

		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel($model_class);

		$this->addEditCategory($page_category);

		$this->deleteCategories($page_category);

		$category_id = NULL;
		if ((int)$this->request->requestParam('category')) {
			$category_id = (int)$this->request->requestParam('category');
		}
		$categ_data = $page_category->getAllByParent();

		$categs_by_id = [];
		foreach ($categ_data as $parent_id => &$categories) {
			foreach ($categories as &$category) {
				$categs_by_id[$category['id']] = $category;

				// if this category is a parent
				if (!is_null($category['id']) && isset($categ_data[$category['id']])) {
					$category['num_subcategories'] = count($categ_data[$category['id']]);
					$category['subcategories'] = &$categ_data[$category['id']];
				}
			}
		}

		// $category is a reference, delete it to avoid altering the last assigned value
		unset($category);

		// re-open the category
		$open_categories = [];
		if ((int)$this->request->requestParam('category')) {
			$category = $categs_by_id[(int)$this->request->requestParam('category')];
			while (!is_null($category)) {
				$open_categories[] = $category['id'];
				$parent = $category['parent'];
				$category = $parent ? $categs_by_id[$parent] : NULL;
			}
		}

		$controller = $this->request->getControllerName();
		$method     = $this->request->getMethodName();
		$title      = $this->url->getLinkText($controller, $method);

		$data = [
			'categories' => isset($categ_data[NULL]) ? $categ_data[NULL] : [],
			'open_categories' => array_reverse($open_categories),
			'allow_subcategories' => $allow_subcategories,
			'readonly' => $readonly,
			'title' => $title,
		];

		$template = $this->getTemplate('pages/administrator/category_manager.php', $data);
		$this->response->setContent($template->render());
	}

	protected function addEditCategory(Model $model) {
		$model->site_id = $this->config->siteConfig()->site_id;

		if ((int)$this->request->requestParam('add_category')) {
			$model->name = $this->request->requestParam('name');
			$model->insert();
		}
		elseif ((int)$this->request->requestParam('edit_category')) {
			$model = $model->get(['id' => (int)$this->request->requestParam('category')]);
			$model->name = $this->request->requestParam('name');
			$model->update();
		}
		elseif ((int)$this->request->requestParam('add_subcategory')) {
			$model->name = $this->request->requestParam('name');
			$model->parent_id = (int)$this->request->requestParam('category');
			$model->insert();
		}
	}

	protected function deleteCategories(Model $model) {
		if ($this->request->requestParam('selected')) {
			$by_parent = $model->getAllByParent();

			foreach ($this->request->requestParam('selected') as $id) {
				if (isset($by_parent[$id])) {
					foreach ($by_parent[$id] as $sub_category) {
						$this->deleteSubcategories($model, $sub_category, $by_parent);
					}
				}

				$category = $model->get(['id' => (int)$id]);
				$category->delete();
			}
		}
	}

	protected function deleteSubcategories(Model $model, $category, array $by_parent) {
		if (isset($by_parent[$category['id']])) {
			foreach ($by_parent[$category['id']] as $sub_category) {
				$this->deleteSubcategories($model, $sub_category, $by_parent);
			}
		}

		$category = $model->get(['id' => (int)$category['id']]);
		$category->delete();
	}
}