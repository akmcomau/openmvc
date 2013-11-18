<?php

namespace core\classes;

use ErrorException;
use Logger as Log4phpLogger;

class Logger extends Log4phpLogger {

	public static function init() {
		// Add some data to the server method
		$_SERVER['controller'] = isset($_REQUEST['controller']) ? $_REQUEST['controller'] : '';
		$_SERVER['method'] = isset($_REQUEST['method']) ? $_REQUEST['method'] : '';
		$_SERVER['params'] = isset($_REQUEST['params']) ? $_REQUEST['params'] : '';

		$filename = __DIR__.DS.'..'.DS.'..'.DS.'core'.DS.'config'.DS.'log4php.json';
		$contents = file_get_contents($filename);
		$config   = json_decode($contents, TRUE);
		if (!$config) {
			throw new ErrorException("Could not decode Log4php config file $filename");
		}
		Log4phpLogger::configure($config);
	}

}