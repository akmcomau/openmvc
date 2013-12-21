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
$database   = new Database(
	$config->database->engine,
	$config->database->hostname,
	$config->database->username,
	$config->database->database,
	$config->database->password
);

if (!isset($argv[1])) {
	$logger->error('You must pass in a domain name as the first argument');
	exit(1);
}
$config->setSiteDomain('www.'.$argv[1]);

$model = new Model($config, $database);
$model->createDatabase();
