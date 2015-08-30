#!/usr/bin/env php
<?php

use core\classes\Database;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Model;
use core\classes\Module;
use core\classes\AutoLoader;

include('core/ErrorHandler.php');
include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();

$logger     = Logger::getLogger('');
$config     = new Config();
$database   = new Database($config);

foreach ($config->sites as $site) {
	$logger->info("Updating config for site: ".$site->namespace);
	$config->setSiteDomain('www.'.$site->domain);
	Module::clearCache();
	$config->updateConfig();
}
