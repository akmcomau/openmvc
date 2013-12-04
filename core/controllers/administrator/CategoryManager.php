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
	protected $controller_class = 'administrator/CategoryManager';

	protected $permissions = [];

	protected function category_manager($message, $model_class, $allow_subcategories = TRUE, $readonly = FALSE) {
		$this->language->loadLanguageFile('administrator/category_manager.php');

		$model = new Model($this->config, $this->database);
		$page_category = $model->getModel($model_class);

		$this->addEditCategory($page_category);

		$this->deleteCategories($page_category);

		// Limit to particular site
		$site_id = $this->allowedSiteIDs();

		$category_id = NULL;
		if ((int)$this->request->requestParam('category')) {
			$category_id = (int)$this->request->requestParam('category');
		}
		$categ_data = $page_category->getAllByParent($site_id);

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
			'categories' => isset($categ_data[NULL]) ? $categ_data[NULL] : [],
			'open_categories' => array_reverse($open_categories),
			'allow_subcategories' => $allow_subcategories,
			'readonly' => $readonly,
			'title' => $title,
			'message_js' => $message_js,
			'controller_name' => $controller,
			'method_name' => $method,
		];

		$template = $this->getTemplate('pages/administrator/category_manager.php', $data);
		$this->response->setContent($template->render());
	}

	protected function addEditCategory(Model $model) {
		$model->site_id = $this->config->siteConfig()->site_id;

		if ((int)$this->request->requestParam('add_category')) {
			$model->name = $this->request->requestParam('name');
			$model->insert();

			throw new RedirectException($this->url->getUrl($this->controller_class, $this->request->getMethodName(), ['add-success']));
		}
		elseif ((int)$this->request->requestParam('edit_category')) {
			$model = $model->get(['id' => (int)$this->request->requestParam('category')]);
			$model->name = $this->request->requestParam('name');
			$model->update();

			throw new RedirectException($this->url->getUrl($this->controller_class, $this->request->getMethodName(), ['update-success']));
		}
		elseif ((int)$this->request->requestParam('add_subcategory')) {
			$model->name = $this->request->requestParam('name');
			$model->parent_id = (int)$this->request->requestParam('category');
			$model->insert();

			throw new RedirectException($this->url->getUrl($this->controller_class, $this->request->getMethodName(), ['add-success']));
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

			throw new RedirectException($this->url->getUrl($this->controller_class, $this->request->getMethodName(), ['delete-success']));
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