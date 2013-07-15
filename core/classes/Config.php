<?php

namespace core\classes;

use ErrorException;

class Config {

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
}
