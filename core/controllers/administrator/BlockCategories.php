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
		if ($this->config->database->engine == 'none') {
			$template = $this->getTemplate('pages/administrator/database_required.php');
			$this->response->setContent($template->render());
			return;
		}

		$this->category_manager($message, '\core\classes\models\BlockCategory', FALSE);
	}
}
