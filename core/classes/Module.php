<?php

namespace core\classes;

use ErrorException;
use core\classes\URL;

class Module {

	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	protected static $modules = NULL;

	protected static $block_types = NULL;

	public function __construct(Config $config) {
		$this->config = $config;
	}

	public static function clearCache() {
		self::$modules = NULL;
	}

	public function getModules() {
		if (self::$modules) return self::$modules;

		$modules_enabled = [];
		foreach ($this->config->sites as $domain => $data) {

			if (property_exists($data, 'modules')) {
				foreach ($data->modules as $module => $module_data) {
					$modules_enabled[$module] = 1;
				}
			}
		}

		// get the paths
		$site = $this->config->siteConfig();
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$core_glob = $root_path.'core'.DS.'modules'.DS.'*'.DS.'module.php';
		$site_glob = $root_path.'modules'.DS.'*'.DS.'module.php';

		// get the modules
		self::$modules = [];
		foreach (glob($core_glob) as $filename) {
			$_MODULE = NULL;
			require($filename);

			if (!(isset($_MODULE['hidden']) && $_MODULE['hidden'])) {
				self::$modules[$_MODULE['namespace']] = $_MODULE;
			}
		}
		foreach (glob($site_glob) as $filename) {
			$_MODULE = NULL;
			require($filename);

			if (!(isset($_MODULE['hidden']) && $_MODULE['hidden'])) {
				self::$modules[$_MODULE['namespace']] = $_MODULE;
			}
		}

		// check if the module is installed into the site
		foreach (self::$modules as &$module) {
			if (in_array($module['namespace'], $this->config->modules)) {
				$module['installed'] = TRUE;
			}
			else {
				$module['installed'] = FALSE;
			}
			if ($site->modules && property_exists($site->modules, $module['namespace'])) {
				$module['enabled'] = TRUE;
			}
			else {
				$module['enabled'] = FALSE;
			}
			$module['enabled_anywhere'] = isset($modules_enabled[$module['namespace']]);
		}

		return self::$modules;
	}

	public function getBlockTypes(Database $database) {
		if (self::$block_types) {
			return self::$block_types;
		}

		$url = new URL($this->config, FALSE);

		$model = new Model($this->config, $database);
		$model = $model->getModel('\core\classes\models\BlockType');
		$types = $model->getMulti();
		$type_ids = [];
		foreach ($types as $type) {
			$type_ids[$type->name] = $type->id;
		}

		$model = new Model($this->config, $database);
		$block_type = $model->getModel('\core\classes\models\BlockType');
		$html_model = $block_type->get(['name' => 'HTML']);
		$html_block = [
			'name' => 'HTML',
			'canonical' => 'html',
			'id' => $html_model->id,
			'model' => '\modules\block_events\classes\models\BlockEvent',
			'controller' => 'administrator\BlockEvents',
		];
		self::$block_types = [
			'id' => [$html_block['id'] => $html_block],
			'name' => [$html_block['name'] => $html_block],
			'canonical' => [$html_block['canonical'] => $html_block],
		];

		$modules = $this->getModules();
		foreach ($modules as $module) {
			if (isset($module['block_types'])) {
				foreach ($module['block_types'] as $type => $data) {
					$canonical = $url->canonical($type);
					$data['id'] = isset($type_ids[$type]) ? $type_ids[$type] : NULL;
					$data['name'] = $type;
					$data['canonical'] = $canonical;

					self::$block_types['name'][$type] = &$data;
					self::$block_types['canonical'][$canonical] = &$data;
					self::$block_types['id'][$data['id']] = &$data;
				}
			}
		}

		return self::$block_types;
	}

	public function getEnabledModules() {
		$enabled = [];
		$modules = $this->getModules();
		foreach ($modules as $module) {
			if ($module['enabled']) {
				$enabled[] = $module;
			}
		}

		return $enabled;
	}

	public function isModuleEnabled($namespace) {
		$enabled = [];
		$modules = $this->getModules();
		foreach ($modules as $module_namespace => $module) {
			if ($module['enabled'] && $module_namespace == $namespace) {
				return TRUE;
			}
		}

		return FALSE;
	}

	public function install($module_namespace, Database $database) {
		if (!isset($this->getModules()[$module_namespace])) {
			throw new ErrorException("No module named $module_namespace");
		}

		$module = $this->getModules()[$module_namespace];

		$installer_class = $module['namespace'].'\\Installer';
		$installer = new $installer_class($this->config, $database);
		$installer->install();

		$this->config->installModule($module);
	}

	public function uninstall($module_namespace, Database $database) {
		if (!isset($this->getModules()[$module_namespace])) {
			throw new ErrorException("No module named $module_namespace");
		}
		$module = $this->getModules()[$module_namespace];

		$installer_class = $module['namespace'].'\\Installer';
		$installer = new $installer_class($this->config, $database);
		$installer->uninstall();

		$this->config->uninstallModule($module);
	}

	public function enable($module_namespace, Database $database) {
		if (!isset($this->getModules()[$module_namespace])) {
			throw new ErrorException("No module named $module_namespace");
		}
		$module = $this->getModules()[$module_namespace];

		$installer_class = $module['namespace'].'\\Installer';
		$installer = new $installer_class($this->config, $database);
		$installer->enable();

		$this->config->enableModule($module);
	}

	public function disable($module_namespace, Database $database) {
		if (!isset($this->getModules()[$module_namespace])) {
			throw new ErrorException("No module named $module_namespace");
		}
		$module = $this->getModules()[$module_namespace];

		$installer_class = $module['namespace'].'\\Installer';
		$installer = new $installer_class($this->config, $database);
		$installer->disable();

		$this->config->disableModule($module);
	}
}
