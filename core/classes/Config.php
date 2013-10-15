<?php

namespace core\classes;

use core\classes\exceptions\DomainRedirectException;
use ErrorException;

class Config {

	protected $site_domain;

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

		// add the custom config to this object
		foreach ($_CONFIG as $key => $value) {
			$this->$key = $value;
		}

		// add the default config to this object
		foreach ($this->sites as $domain => $site_data) {
			foreach ($_DEFAULT_CONFIG->default_site as $key => $value) {
				if (!isset($site_data->$key)) {
					$site_data->$key = $value;
				}
			}
		}
		unset($_DEFAULT_CONFIG->default_site);

		foreach ($_DEFAULT_CONFIG as $key => $value) {
			if (!isset($this->$key)) {
				$this->$key = $value;
			}
		}
	}

	public function installModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['modules'])) $_CONFIG['modules'] = [];

		if (!in_array($module['name'], $_CONFIG['modules'])) {
			$_CONFIG['modules'][] = $module['name'];
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
	}

	public function enableModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'] = [];
		}

		if (!in_array($module['name'], $_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'][$module['name']] = $module['default_config'];
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
	}

	public function disableModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'] = [];
		}

		if (isset($_CONFIG['sites'][$this->site_domain]['modules'][$module['name']])) {
			unset($_CONFIG['sites'][$this->site_domain]['modules'][$module['name']]);
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
	}

	public function siteConfig() {
		return $this->sites->{$this->site_domain};
	}

	public function getSiteDomain() {
		return $this->site_domain;
	}

	public function getSiteURL() {
		return 'http://www.'.$this->site_domain;
	}

	public function setSiteDomain($host) {
		$sites = $this->sites;
		foreach ($sites as $domain => $site) {
			if ($domain == $host) {
				throw new DomainRedirectException('www.'.$domain);
			}
			elseif ('www.'.$domain == $host) {
				$this->site_domain = $domain;
				return;
			}
		}

		throw new ErrorException("HTTP_HOST does not reference a site: $host");
	}
}
