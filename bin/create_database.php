#!/usr/bin/env php
<?php

use core\classes\Database;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Model;
use core\classes\AutoLoader;

include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();

$config     = new Config();
$database   = new Database(
	$config->database->engine,
	$config->database->hostname,
	$config->database->username,
	$config->database->database,
	$config->database->password
);

$model = new Model($config, $database);
$model->createDatabase();
