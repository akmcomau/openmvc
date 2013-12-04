<?php

namespace core\controllers\administrator;

use core\controllers\administrator\CategoryManager;

class PageCategories extends CategoryManager {

	protected $show_admin_layout = TRUE;
	protected $controller_class = 'administrator/PageCategories';

	protected $permissions = [
		'index' => ['administrator'],
	];

	public function index($message = NULL) {
		$this->category_manager($message, '\core\classes\models\PageCategory', FALSE);
	}

}