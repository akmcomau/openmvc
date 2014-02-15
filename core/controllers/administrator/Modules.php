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
			$module['install_url'] = $this->url->getUrl('administrator/Modules', 'install', [$module['namespace']]);
			$module['uninstall_url'] = $this->url->getUrl('administrator/Modules', 'uninstall', [$module['namespace']]);
			$module['enable_url'] = $this->url->getUrl('administrator/Modules', 'enable', [$module['namespace']]);
			$module['disable_url'] = $this->url->getUrl('administrator/Modules', 'disable', [$module['namespace']]);
		}

		$data = [
			'modules' => $all_modules,
		];

		$template = $this->getTemplate('pages/administrator/modules.php', $data);
		$this->response->setContent($template->render());
	}

	public function install($module_namespace) {
		$modules = new Module($this->config);
		$modules->install($module_namespace, $this->database);
		throw new RedirectException($this->url->getUrl('administrator/Modules'));
	}

	public function uninstall($module_namespace) {
		$modules = new Module($this->config);
		$modules->uninstall($module_namespace, $this->database);
		throw new RedirectException($this->url->getUrl('administrator/Modules'));
	}

	public function enable($module_namespace) {
		$modules = new Module($this->config);
		$modules->enable($module_namespace, $this->database);
		throw new RedirectException($this->url->getUrl('administrator/Modules'));
	}

	public function disable($module_namespace) {
		$modules = new Module($this->config);
		$modules->disable($module_namespace, $this->database);
		throw new RedirectException($this->url->getUrl('administrator/Modules'));
	}
}