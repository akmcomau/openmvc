<?php

namespace core\classes;

use ErrorException;
use Logger as Log4phpLogger;

class Logger extends Log4phpLogger {

	public static function init() {
		$filename = __DIR__.DS.'..'.DS.'..'.DS.'core'.DS.'config'.DS.'log4php.json';
		$contents = file_get_contents($filename);
		$config   = json_decode($contents, TRUE);
		if (!$config) {
			throw new ErrorException("Could not decode Log4php config file $filename");
		}
		Log4phpLogger::configure($config);
	}

}