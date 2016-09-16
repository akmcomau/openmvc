#!/usr/bin/env php
<?php

// drop all tables in postgres database
//   drop schema public cascade;
//   create schema public;

use core\classes\Database;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Model;
use core\classes\AutoLoader;

include('core/ErrorHandler.php');
include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();

$logger     = Logger::getLogger('');
$config     = new Config();

if (isset($argv[1])) {
	$config->setSiteDomain($argv[1], FALSE);
}

$database   = new Database($config);
$model = new Model($config, $database);
$updates = $model->checkDatabase();

// Ask if the changes should be applied
print_r($updates);
print "\n\nApply changes [y,n]: ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(!(trim($line) == 'y' || trim($line) == 'Y')) {
	echo "\nABORTING!\n";
	exit;
}

$model->updateDatabase($updates);
