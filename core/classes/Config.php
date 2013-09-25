<?php

namespace core\classes;

use ErrorException;

class Config {

	protected $site_domain;

	public function __construct() {
		// get the default config
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'default_config.json';
		$content = file_get_contents($filename);
		$default = json_decode($content);
		if (!$default) {
			throw new ErrorException("Could not decode config file: $filename");
		}

		// get the custom config
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.json';
		$content = file_get_contents($filename);
		$custom = json_decode($content);
		if (!$custom) {
			throw new ErrorException("Could not decode config file: $filename");
		}

		// add the custom config to this object
		foreach ($custom as $key => $value) {
			$this->$key = $value;
		}

		// add the default config to this object
		foreach ($this->sites as $domain => $site_data) {
			foreach ($default->default_site as $key => $value) {
				if (!isset($site_data->$key)) {
					$site_data->$key = $value;
				}
			}
		}
		unset($default->default_site);

		foreach ($default as $key => $value) {
			if (!isset($this->$key)) {
				$this->$key = $value;
			}
		}
	}

	public function setDomain($site_domain) {
		$this->site_domain = $site_domain;
	}

	public function getDomain() {
		return $this->site_domain;
	}

	public function getSiteParams() {
		return $this->sites->{$this->site_domain};
	}
}
