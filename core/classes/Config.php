<?php

namespace core\classes;

use ErrorException;

class Config {

	protected $site_domain;

	public function __construct() {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.json';
		$content = file_get_contents($filename);
		$json = json_decode($content);
		if (!$json) {
			throw new ErrorException("Could not decode config file: $filename");
		}

		foreach ($json as $key => $value) {
			$this->$key = $value;
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
