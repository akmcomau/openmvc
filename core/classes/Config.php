<?php

namespace core\classes;

use core\classes\exceptions\DomainRedirectException;
use ErrorException;

class Config {

	protected $site_domain;
	protected $configuration = [];

	public function __construct() {
		// get the default config
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'default_config.php';
		require($filename);

		// get the custom config
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		// Convert the config data to objects
		$_CONFIG = json_decode(json_encode($_CONFIG), FALSE);
		$_DEFAULT_CONFIG = json_decode(json_encode($_DEFAULT_CONFIG), FALSE);

		$this->configuration = [];

		// add the custom config to this object
		foreach ($_CONFIG as $key => $value) {
			$this->configuration[$key] = $value;
		}

		// add the default config to this object
		foreach ($this->configuration['sites'] as $domain => &$site_data) {
			foreach ($_DEFAULT_CONFIG->default_site as $key => $value) {
				if (!isset($site_data->$key)) {
					$site_data->$key = $value;
				}
			}
			$site_data->domain = $domain;
		}
		unset($_DEFAULT_CONFIG->default_site);

		foreach ($_DEFAULT_CONFIG as $key => $value) {
			if (!isset($this->configuration[$key])) {
				$this->configuration[$key] = $value;
			}
		}

		// default value for is_robot
		$this->configuration['is_robot'] = FALSE;
	}

	public function setRobot($value) {
		if ($value) {
			$this->configuration['is_robot'] = TRUE;
		}
		else {
			$this->configuration['is_robot'] = FALSE;
		}
	}

	public function __get($name) {
		if (isset($this->configuration[$name])) {
			return $this->configuration[$name];
		}
		throw new ErrorException("Undefined config property: $name");
	}

	public function installModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['modules'])) $_CONFIG['modules'] = [];

		if (!in_array($module['namespace'], $_CONFIG['modules'])) {
			$_CONFIG['modules'][] = $module['namespace'];
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	public function uninstallModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['modules'])) $_CONFIG['modules'] = [];

		if (in_array($module['namespace'], $_CONFIG['modules'])) {
			$index = array_search($module['namespace'], $_CONFIG['modules']);
			array_splice($_CONFIG['modules'], $index, 1);
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	public function enableModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'] = [];
		}

		if (!in_array($module['namespace'], $_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'][$module['namespace']] = $module['default_config'];
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	public function moduleConfig($module) {
		try {
			return $this->sites->{$this->site_domain}->modules->$module;
		}
		catch (\Exception $ex) {
			throw new ErrorException("Module config not found for $module: ".$ex->getMessage());
		}
	}

	public function disableModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'] = [];
		}

		if (isset($_CONFIG['sites'][$this->site_domain]['modules'][$module['namespace']])) {
			unset($_CONFIG['sites'][$this->site_domain]['modules'][$module['namespace']]);
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	public function siteConfig() {
		return $this->sites->{$this->site_domain};
	}

	public function updateSiteConfigParam($name, $value) {
		$this->sites->{$this->site_domain}->{$name} = $value;
	}

	public function updateConfigParam($name, $value) {
		$this->sites->{$name} = $value;
	}

	public function getSiteConfig() {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'] = [];
		}

		return $_CONFIG;
	}

	public function setSiteConfig($config) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($config, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	public function getUrl() {
		return 'http://www.'.$this->site_domain;
	}

	public function getSiteDomain() {
		return $this->site_domain;
	}

	public function getSiteUrl() {
		return 'http://www.'.$this->site_domain;
	}

	public function setSiteDomain($host, $redirect = TRUE) {
		$default_site = NULL;
		$sites = $this->sites;
		foreach ($sites as $domain => $site) {
			if ($redirect && $domain == $host) {
				throw new DomainRedirectException('www.'.$domain);
			}
			elseif ('www.'.$domain == $host || (!$redirect && $domain == $host)) {
				$this->site_domain = $domain;

				// set locale
				setlocale(LC_ALL, $this->siteConfig()->locale);

				return;
			}

			if (!$default_site && $site->default_site) {
				$default_site = $site;
			}
		}

		// redirect to the first default site in the list
		if ($default_site) {
			throw new DomainRedirectException('www.'.$default_site->domain);
		}

		throw new ErrorException("HTTP_HOST does not reference a site: $host");
	}
}
