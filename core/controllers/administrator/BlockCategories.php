<?php

namespace core\controllers\administrator;

use core\controllers\administrator\CategoryManager;

class BlockCategories extends CategoryManager {

	protected $show_admin_layout = TRUE;
	protected $controller_class = 'administrator/BlockCategories';

	protected $permissions = [
		'index' => ['administrator'],
	];

	public function index($message = NULL) {
		$this->category_manager($message, '\core\classes\models\BlockCategory', FALSE);
	}
}
