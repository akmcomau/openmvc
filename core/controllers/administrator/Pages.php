<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;

function sort_pages($a, $b) {
	return 0;
}

class Pages extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
	];

	public function index() {
		$this->language->loadLanguageFile('administrator/pages.php');

		// get all the pages
		$pages = [];
		$controllers = $this->url->listAllControllers();
		foreach ($controllers as $controller_name => $controller_class) {
			$controller = new $controller_class($this->config, $this->database, $this->request, $this->response);
			$methods = $controller->getAllMethods();
			$permissions = $controller->getPermissions();
			foreach ($methods as $method) {
				$meta_tags = $this->url->getMethodMetaTags($controller_name, $method, FALSE);
				$method = [
					'url' => $this->url->seoController($controller_name).'/'.$method,
					'title' => $meta_tags['title'],
					'permissions' => isset($permissions[$method]) ? join(', ', $permissions[$method]) : '',
					'controller' => $controller_name,
					'method' => $method,
				];
				$pages[] = $method;
			}
		}

		$sort_field = 'url';
		usort($pages, function ($a, $b) use ($sort_field) {
			if ($a[$sort_field] < $b[$sort_field]) return -1;
			if ($a[$sort_field] > $b[$sort_field]) return 1;
			return 0;
		});

		$data = [
			'pages' => $pages,
		];

		$template = $this->getTemplate('pages/administrator/pages/list.php', $data);
		$this->response->setContent($template->render());
	}

	public function add() {
		$template = $this->getTemplate('pages/not_implemented.php');
		$this->response->setContent($template->render());
	}
}