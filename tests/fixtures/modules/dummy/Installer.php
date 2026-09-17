<?php

namespace tests\fixtures\modules\dummy;

use core\classes\Config;
use core\classes\Database;

/**
 * A minimal Installer used by ModuleTest.php to exercise
 * core\classes\Module's install/uninstall/enable/disable wrappers without a
 * real module or database - each call just records that it happened, so
 * tests can assert the wrapper actually delegated to the Installer (and not
 * just to Config).
 */
class Installer {

	public static array $calls = [];

	public function __construct(Config $config, Database $database) {
	}

	public function install() {
		self::$calls[] = 'install';
	}

	public function uninstall() {
		self::$calls[] = 'uninstall';
	}

	public function enable() {
		self::$calls[] = 'enable';
	}

	public function disable() {
		self::$calls[] = 'disable';
	}
}
