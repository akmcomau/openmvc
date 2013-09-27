<?php

namespace core\classes;

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

	public function getDomain() {
		return $this->site_domain;
	}

	public function siteConfig() {
		return $this->sites->{$this->site_domain};
	}

	public function setSiteDomain($host) {
		$sites = $this->sites;
		foreach ($sites as $domain => $site) {
			if ($domain == $host || 'www.'.$domain == $host) {
				$this->site_domain = $domain;
				return;
			}
		}

		throw new ErrorException("HTTP_HOST does not reference a site: $host");
	}
}
