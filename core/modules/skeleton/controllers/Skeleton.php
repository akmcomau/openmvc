<?php

namespace core\modules\skeleton\controllers;

use core\classes\renderable\Controller;

class Skeleton extends Controller {
	public function index() {
		$this->language->loadLanguageFile('skeleton.php', 'core'.DS.'modules'.DS.'skeleton');
		$template = $this->getTemplate('pages/skeleton.php', [], 'core'.DS.'modules'.DS.'skeleton');
		$this->response->setContent($template->render());
	}
}