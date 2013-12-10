<?php

namespace core\widgets;

use core\classes\exceptions\RenderableException;
use core\classes\Model;
use core\classes\renderable\Widget;

class CategoryGrid extends Widget {

	protected $categories = [];

	public function getCategoryCount() {
		return count($this->categories);
	}

	public function getCategories($class, array $params = NULL, array $ordering = NULL, array $pagination = NULL) {
		$model = new Model($this->config, $this->database);
		$category = $model->getModel($class);
		$this->categories = $category->getMulti($params, $ordering, $pagination);
	}

	public function render() {
		$data = ['categories' => $this->categories];
		$template = $this->getTemplate('widgets/category_grid.php', $data);
		return $template->render();
	}
}