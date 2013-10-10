<?php

namespace core\modules\skeleton;

use core\classes\Config;
use core\classes\Database;

class Installer {
	protected $config;
	protected $database;

	public function __construct(Config $config, Database $database) {
		$this->config = $config;
		$this->database = $database;
	}

	public function install() {

	}

	public function enable() {

	}

	public function disable() {

	}
}