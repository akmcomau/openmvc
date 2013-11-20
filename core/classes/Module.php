<?php

namespace core\classes;

use ErrorException;

class Module {

	protected $config;

	protected $modules = NULL;

	public function __construct(Config $config) {
		$this->config = $config;
	}

	public function getModules() {
		if (isset($GLOBALS['cache']['modules'])) {
			$this->modules = $GLOBALS['cache']['modules'];
		}
		if ($this->modules) return $this->modules;

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
		$core_glob = $root_path.'core'.DS.'modules'.DS.'*'.DS.'module.json';
		$site_glob = $root_path.'modules'.DS.'*'.DS.'module.json';

		// get the modules
		$this->modules = [];
		foreach (glob($core_glob) as $filename) {
			$contents = file_get_contents($filename);
			$json = json_decode($contents, TRUE);
			if (!$json) {
				throw new ErrorException("Could not decode: $filename");
			}

			if (!(isset($json['hidden']) && $json['hidden'])) {
				$this->modules[$json['name']] = $json;
			}
		}
		foreach (glob($site_glob) as $filename) {
			$contents = file_get_contents($filename);
			$json = json_decode($contents, TRUE);
			if (!$json) {
				throw new ErrorException("Could not decode: $filename");
			}

			if (!(isset($json['hidden']) && $json['hidden'])) {
				$this->modules[$json['name']] = $json;
			}
		}

		// check if the module is installed into the site
		foreach ($this->modules as &$module) {
			if (in_array($module['name'], $this->config->modules)) {
				$module['installed'] = TRUE;
			}
			else {
				$module['installed'] = FALSE;
			}
			if ($site->modules && property_exists($site->modules, $module['name'])) {
				$module['enabled'] = TRUE;
			}
			else {
				$module['enabled'] = FALSE;
			}
			$module['enabled_anywhere'] = isset($modules_enabled[$module['name']]);
		}

		$GLOBALS['cache']['modules'] = $this->modules;

		return $this->modules;
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

	public function isModuleEnabled($name) {
		$enabled = [];
		$modules = $this->getModules();
		foreach ($modules as $module_name => $module) {
			if ($module['enabled'] && $module_name == $name) {
				return TRUE;
			}
		}

		return FALSE;
	}

	public function install($module_name, Database $database) {
		if (!isset($this->getModules()[$module_name])) {
			throw new ErrorException("No module named $module_name");
		}
		$module = $this->getModules()[$module_name];

		$installer_class = $module['namespace'].'\\Installer';
		$installer = new $installer_class($this->config, $database);
		$installer->install();

		$this->config->installModule($module);
	}

	public function uninstall($module_name, Database $database) {
		if (!isset($this->getModules()[$module_name])) {
			throw new ErrorException("No module named $module_name");
		}
		$module = $this->getModules()[$module_name];

		$installer_class = $module['namespace'].'\\Installer';
		$installer = new $installer_class($this->config, $database);
		$installer->uninstall();

		$this->config->uninstallModule($module);
	}

	public function enable($module_name, Database $database) {
		if (!isset($this->getModules()[$module_name])) {
			throw new ErrorException("No module named $module_name");
		}
		$module = $this->getModules()[$module_name];

		$installer_class = $module['namespace'].'\\Installer';
		$installer = new $installer_class($this->config, $database);
		$installer->enable();

		$this->config->enableModule($module);
	}

	public function disable($module_name, Database $database) {
		if (!isset($this->getModules()[$module_name])) {
			throw new ErrorException("No module named $module_name");
		}
		$module = $this->getModules()[$module_name];

		$installer_class = $module['namespace'].'\\Installer';
		$installer = new $installer_class($this->config, $database);
		$installer->disable();

		$this->config->disableModule($module);
	}
}