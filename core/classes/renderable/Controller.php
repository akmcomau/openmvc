<?php

namespace core\classes\renderable;

use ReflectionClass;
use ReflectionMethod;
use core\classes\Authentication;
use core\classes\Config;
use core\classes\Database;
use core\classes\Logger;
use core\classes\Response;
use core\classes\Request;
use core\classes\URL;
use core\classes\Template;
use core\classes\Language;
use core\classes\Renderable;
use core\classes\renderable\Layout;
use core\classes\Menu;

class Controller extends Renderable {

	protected $show_admin_layout = FALSE;

	protected $request;
	protected $response;
	protected $layout;
	protected $url;
	protected $authentication;
	protected $language;

	protected $permissions = [];

	public function __construct(Config $config, Database $database = NULL, Request $request = NULL, Response $response = NULL) {

		// Controller has been created for meta data, don't need much
		if (is_null($request)) {
			$this->config = $config;
			return;
		}

		parent::__construct($config, $database);

		$this->request        = $request;
		$this->response       = $response;
		$this->url            = new URL($config);
		$this->language       = new Language($config);
		$this->authentication = new Authentication($config, $database, $request);

		$menu = NULL;
		if ($this->show_admin_layout) {
			$layout_class = $config->siteConfig()->admin_layout_class;
			if ($this->config->siteConfig()->enable_admin) {
				$layout_template = $config->siteConfig()->admin_layout_template;
			}
			else {
				$layout_template = 'layouts/bare.php';
			}
		}
		else {
			$layout_class = $config->siteConfig()->default_layout_class;
			if ($this->config->siteConfig()->enable_public) {
				$layout_template = $config->siteConfig()->default_layout_template;
			}
			else {
				$layout_template = 'layouts/bare.php';
			}
		}

		$this->layout = new $layout_class($config, $database, $request, $response, $this->authentication, $this->language, $layout_template);

		if ($this->show_admin_layout) {
			if ($this->config->siteConfig()->enable_admin) {
				$this->layout->loadLanguageFile('administrator/layout.php');
			}
		}
		else {
			if ($this->config->siteConfig()->enable_public) {
				$this->layout->loadLanguageFile('layout.php');
			}
		}
	}

	public function setUrl($url) {
		return $this->url = $url;
	}

	public function getRequest() {
		return $this->request;
	}

	public function getAuthentication() {
		return $this->authentication;
	}

	public function getPermissions() {
		return $this->permissions;
	}

	public function getLayout() {
		return $this->layout;
	}

	public function getTemplate($filename, array $data = NULL) {
		return new Template($this->config, $this->language, $filename, $data);
	}

	public function setLayout(Layout $layout = NULL) {
		$this->layout = $layout;
	}

	public function render() {
		if ($this->show_admin_layout) {
			$filename = __DIR__.DS.'..'.DS.'..'.DS.'config'.DS.'menu_admin_main.php';
			$main_menu = new Menu($this->config, $this->language, 'menus/admin_main.php', 'mainnav');
			require($filename);
			$main_menu->setData($_MENU);

			$filename = __DIR__.DS.'..'.DS.'..'.DS.'config'.DS.'menu_admin_user.php';
			$user_menu = new Menu($this->config, $this->language, 'menus/admin_user.php', 'nav navbar-nav navbar-right');
			require($filename);
			$user_menu->setData($_MENU);

			if ($this->layout) {
				$this->layout->setTemplateData([
					'main_menu' => $main_menu,
					'user_menu' => $user_menu,
				]);
			}
		}
		else {
			$filename = __DIR__.DS.'..'.DS.'..'.DS.'config'.DS.'menu_admin_panel.php';
			$admin_panel = new Menu($this->config, $this->language, 'menus/admin_panel.php', 'nav navbar-nav navbar-right');
			require($filename);
			if (isset($_MENU['language'])) {
				$_MENU['language']['params'] = $this->language->getLoadedFiles();
			}
			if (isset($_MENU['edit_page'])) {
				$_MENU['edit_page']['params'] = [$this->request->getControllerName(), $this->request->getMethodName()];
			}
			$admin_panel->setData($_MENU);

			if ($this->layout) {
				$this->layout->setTemplateData([
					'admin_panel' => $admin_panel,
				]);
			}
		}
	}

	public function getAllMethods() {
		$class = new ReflectionClass(__CLASS__);
		$ignored = $class->getMethods(ReflectionMethod::IS_PUBLIC);
		$class = new ReflectionClass(get_class($this));
		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

		$controller_methods = [];
		foreach ($methods as $method) {
			$ignore_method = FALSE;
			foreach ($ignored as $ignore) {
				if ($ignore->name == $method->name) {
					$ignore_method = TRUE;
				}
			}
			if (!$ignore_method) {
				$controller_methods[] = $method->name;
			}
		}

		return $controller_methods;
	}
}
