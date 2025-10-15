<?php
namespace core\classes;

use core\classes\exceptions\AutoLoaderException;

/**
 * To AutoLoad all classes
 */
class AutoLoader {

	/**
	 * Initialise the AutoLoader
	 */
	public static function init() {
		$root_path = __DIR__.'/../..';

		// autoload OpenMVC classes
		spl_autoload_register(function ($class) use ($root_path) {
			$file = str_replace('\\', DS, $class);
			$filename = $root_path.DS.$file.'.php';
			if (file_exists($filename)) {
				include($filename);
			}

			$logger_path = $root_path.DS.'composer/vendor/apache/log4php/src/main/php/'.$class.'.php';
			if (file_exists($logger_path)) {
				include($logger_path);
			}
		});

		// autoload main composer
		include("$root_path/composer/vendor/autoload.php");

		// include site composer
		$glob = $root_path.'/sites/*/classes/Autoloader.php';
		foreach (glob($glob) as $filename) {
			include($filename);
		}

		// include modules composer
		$glob = $root_path.'/modules/*/classes/Autoloader.php';
		foreach (glob($glob) as $filename) {
			include($filename);
		}
	}

}
