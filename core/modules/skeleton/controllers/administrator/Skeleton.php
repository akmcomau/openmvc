<?php

namespace core\modules\skeleton\controllers\administrator;

use core\classes\renderable\Controller;

class Skeleton extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
	];

	public function index() {
		$this->language->loadLanguageFile('administrator/skeleton.php', 'core'.DS.'modules'.DS.'skeleton');
	}
}