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

		// argv causes "Array to string conversion" notices
		$argv = NULL;
		if (isset($_SERVER['argv'])) {
			$argv = $_SERVER['argv'];
			$_SERVER['argv'] = json_encode($argv);
		}

		$root_dir = __DIR__.DS.'..'.DS.'..'.DS;
		$filename = $root_dir.'core'.DS.'config'.DS.'log4php.php';
		require($filename);
		if (!$_LOG4PHP) {
			throw new ErrorException("Could not read Log4php config file: $filename");
		}
		Log4phpLogger::configure($_LOG4PHP);

		if ($argv) {
			$_SERVER['argv'] = $argv;
		}
	}

}