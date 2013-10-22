<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;
use core\classes\Module;

class Modules extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
		'install' => ['administrator'],
		'enable' => ['administrator'],
		'disable' => ['administrator'],
	];

	public function index() {
		$this->language->loadLanguageFile('administrator/modules.php');

		$modules = new Module($this->config);
		$all_modules = $modules->getModules();

		foreach ($all_modules as &$module) {
			$module['install_url'] = $this->url->getURL('administrator/Modules', 'install', [$module['name']]);
			$module['uninstall_url'] = $this->url->getURL('administrator/Modules', 'uninstall', [$module['name']]);
			$module['enable_url'] = $this->url->getURL('administrator/Modules', 'enable', [$module['name']]);
			$module['disable_url'] = $this->url->getURL('administrator/Modules', 'disable', [$module['name']]);
		}

		$data = [
			'modules' => $all_modules,
		];

		$template = $this->getTemplate('pages/administrator/modules.php', $data);
		$this->response->setContent($template->render());
	}

	public function install($module_name) {
		$modules = new Module($this->config);
		$modules->install($module_name, $this->database);
		throw new RedirectException($this->url->getURL('administrator/Modules'));
	}

	public function uninstall($module_name) {
		$modules = new Module($this->config);
		$modules->uninstall($module_name, $this->database);
		throw new RedirectException($this->url->getURL('administrator/Modules'));
	}

	public function enable($module_name) {
		$modules = new Module($this->config);
		$modules->enable($module_name, $this->database);
		throw new RedirectException($this->url->getURL('administrator/Modules'));
	}

	public function disable($module_name) {
		$modules = new Module($this->config);
		$modules->disable($module_name, $this->database);
		throw new RedirectException($this->url->getURL('administrator/Modules'));
	}
}