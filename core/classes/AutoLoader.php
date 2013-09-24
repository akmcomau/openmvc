<?php
namespace core\classes;

use core\classes\exceptions\AutoLoaderException;

class AutoLoader {
	public static function init() {
		$root_path = __DIR__.DS.'..'.DS.'..';

		include("$root_path/composer/vendor/autoload.php");

		spl_autoload_register(function ($class) use ($root_path) {
			$file = str_replace('\\', DS, $class);
			$filename = $root_path.DS.$file.'.php';
			if (file_exists($filename)) {
				include($filename);
			}
			else {
				throw new AutoLoaderException("Attempt to autoload non-existant class: $class");
			}
		});
	}

}

?>