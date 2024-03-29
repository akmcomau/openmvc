<?php

namespace core\classes;

use ErrorException;
use Logger as Log4phpLogger;

class Logger {

	private $logger;

    function __construct(Log4phpLogger $logger) {
		$this->logger = $logger;
    }

	public static function init() {
		// Add some data to the server method
		$_SERVER['controller'] = isset($_REQUEST['controller']) ? $_REQUEST['controller'] : '';
		$_SERVER['method'] = isset($_REQUEST['method']) ? $_REQUEST['method'] : '';
		$_SERVER['params'] = isset($_REQUEST['params']) ? $_REQUEST['params'] : '';

		// argv causes "Array to string conversion" notices in log messages
		$argv = NULL;
		if (isset($_SERVER['argv'])) {
			$argv = $_SERVER['argv'];
			$_SERVER['argv'] = json_encode($argv);
		}

		// configure log4php
		Log4phpLogger::configure(self::getConfig());

		// restore the argv value
		if ($argv) {
			$_SERVER['argv'] = $argv;
		}
	}

	public static function clean($path, $regex, $ttl) {
		// ensure the path ends in a slash and a star
		if ($path[strlen($path)-1] != '/') {
			$path .= '/';
		}
		$path .= '*';

		// get the glob string
		$glob = '';
		if ($path[0] == '/') {
			$glob = $path;
		}
		else {
			$root_dir = __DIR__.DS.'..'.DS.'..'.DS;
			$glob = $root_dir.$path;
		}

		// turn $ttl from days into seconds
		$ttl = $ttl * 24 * 3600;

		// get all the files in the $path
		foreach (glob($glob) as $filename) {
			// check the age
			if (preg_match($regex, $filename, $matches)) {
				$date = strtotime($matches[1]);
				if ((time() - $date) > $ttl) {
					unlink($filename);
				}
			}
		}
	}

	public static function getConfig() {
		$root_dir = __DIR__.DS.'..'.DS.'..'.DS;
		$filename = $root_dir.'core'.DS.'config'.DS.'log4php.php';
		require($filename);
		if (!$_LOG4PHP) {
			throw new ErrorException("Could not read Log4php config file: $filename");
		}
		return $_LOG4PHP;
	}

	public static function getLogger($class = NULL) {
		$logger = Log4phpLogger::getLogger($class);

		return new Logger($logger);
	}

	public function __call ($name, $arguments) {
		return call_user_func_array([$this->logger, $name], $arguments);
	}

	protected function prepareMultiLine($message) {
		return preg_replace('/^/m', '	', $message);
	}

	public function trace($message, $throwable = NULL) {
		$message = $this->prepareMultiLine($message);
		$this->logger->trace($message, $throwable);
	}

	public function debug($message, $throwable = NULL) {
		$message = $this->prepareMultiLine($message);
		$this->logger->debug($message, $throwable);
	}

	public function info($message, $throwable = NULL) {
		$message = $this->prepareMultiLine($message);
		$this->logger->info($message, $throwable);
	}

	public function warn($message, $throwable = NULL) {
		$message = $this->prepareMultiLine($message);
		$this->logger->warn($message, $throwable);
	}

	public function error($message, $throwable = NULL) {
		$message = $this->prepareMultiLine($message);
		$this->logger->error($message, $throwable);
	}

	public function fatal($message, $throwable = NULL) {
		$message = $this->prepareMultiLine($message);
		$this->logger->fatal($message, $throwable);
	}
}
