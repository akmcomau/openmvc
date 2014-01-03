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

$model = new Model($config, $database);
$model->createDatabase();

////////////////////////////
// Insert the inital data
///////////////////////////

// get all the models for all sites
// FIXME: Hack to fix ordering issue
$all_models = [
	'core\classes\models\Country' => [
		'core\classes\models\data\Country',
	],
	'core\classes\models\State' => [
		'core\classes\models\data\State',
	],
	'core\classes\models\City' => [
		'core\classes\models\data\City',
	],
];

foreach ($config->sites as $site) {
	$namespace = $site->namespace;

	$class = "sites\\$namespace\classes\models\data\Country";
	if (!in_array($class, $all_models['core\classes\models\Country'])) {
		$all_models['core\classes\models\Country'][] = $class;
	}

	$class = "sites\\$namespace\classes\models\data\State";
	if (!in_array($class, $all_models['core\classes\models\State'])) {
		$all_models['core\classes\models\State'][] = $class;
	}

	$class = "sites\\$namespace\classes\models\data\City";
	if (!in_array($class, $all_models['core\classes\models\City'])) {
		$all_models['core\classes\models\City'][] = $class;
	}
}

$model = new Model($config, $database);
$models = $model->getSiteModels();

foreach ($models as $model_class) {
	if (!isset($all_models[$model_class])) {

		$model_datas = [];
		foreach ($config->sites as $site) {
			$namespace = $site->namespace;
			// get data in the models namespace
			if (preg_match('/^(.*)\\\\(\w+)$/', $model_class, $matches)) {
				$class = $matches[1].'\data\\'.$matches[2];
				if (!in_array($class, $model_datas)) {
					$model_datas[] = $class;
				}

				$class = 'sites\\'.$namespace.'\\classes\\models\\data\\'.$matches[2];
				if (!in_array($class, $model_datas)) {
					$model_datas[] = $class;
				}
			}
		}

		$all_models[$model_class] = $model_datas;
	}
}

// create the data
foreach ($all_models as $model_class => $data_classes) {
	foreach ($data_classes as $data_class) {
		if (class_exists($data_class)) {
			$object = $model->getModel($model_class);
			$object->insertInitalData($data_class);
		}
	}
}
