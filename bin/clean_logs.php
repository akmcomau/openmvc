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

$logger = Logger::getLogger('');
$config = new Config();

foreach ($config->sites as $site) {
	$logger->info("Cleaning logs for site: ".$site->namespace);
	$config->setSiteDomain('www.'.$site->domain);
	$path = $config->siteConfig()->logger_clean_path;
	$regex = $config->siteConfig()->logger_clean_regex;
	$ttl = $config->siteConfig()->logger_clean_ttl;
	Logger::clean($path, $regex, $ttl);
}
