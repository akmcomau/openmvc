<?php

namespace core\modules\skeleton;

use ErrorException;
use core\classes\Config;
use core\classes\Database;
use core\classes\Language;
use core\classes\Menu;

class Installer {
	protected $config;
	protected $database;

	public function __construct(Config $config, Database $database) {
		$this->config = $config;
		$this->database = $database;
	}

	public function install() {
		$language = new Language($this->config);
		$language->loadLanguageFile('administrator/skeleton.php', 'core'.DS.'modules'.DS.'skeleton'.DS);

		$layout_strings = $language->getFile('administrator/layout.php');
		$layout_strings['skeleton_module_skeleton'] = $language->get('skeleton');
		$language->updateFile('administrator/layout.php', $layout_strings);

		$main = [
			'url' => 'javascript:;',
			'text_tag' => 'skeleton_module_skeleton',
			'icon' => 'fa fa-copy',
		];

		// Add some menu items to the admin menu
		$main_menu = new Menu($this->config, $language);
		$main_menu->loadMenu('menu_admin_main.php');
		$main_menu->insert_menu(['content', 'content_pages'], 'skeleton', $main);
		$main_menu->insert_menu('content', 'skeleton', $main);
		$main_menu->update();
	}

	public function uninstall() {
		$language = new Language($this->config);
		$language->loadLanguageFile('skeleton.php', 'core'.DS.'modules'.DS.'skeleton');

		// remove layout strings
		$layout_strings = $language->getFile('administrator/layout.php');
		unset($layout_strings['skeleton_module_skeleton']);
		$language->updateFile('administrator/layout.php', $layout_strings);

		// Remove some menu items to the admin menu
		$main_menu = new Menu($this->config, $language);
		$main_menu->loadMenu('menu_admin_main.php');
		$menu = $main_menu->getMenuData();

		unset($menu['content']['children']['skeleton']);
		unset($menu['skeleton']);

		$main_menu->setMenuData($menu);
		$main_menu->update();

	}

	public function enable() {

	}

	public function disable() {

	}
}