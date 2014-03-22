#!/usr/bin/env php
<?php

// Need to do more automated db updates one day ...

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

$model_class = $_SERVER['argv'][1];
$model = new Model($config, $database);
$model = $model->getModel($model_class);
$model->createTable();
$model->createForeignKeys();
$model->createIndexes();
