#!/usr/bin/env php
<?php

use core\classes\exceptions\AutoLoaderException;

use core\classes\Database;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Model;
use core\classes\URL;
use core\classes\AutoLoader;

include('core/ErrorHandler.php');
include('core/Constants.php');
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

if (!isset($argv[1])) {
	$logger->error('You must pass in a domain name as the first argument');
	exit(1);
}
$config->setSiteDomain('www.'.$argv[1]);

// get all the models for all sites
// FIXME: Hack to fix ordering issue
$namespace = $config->siteConfig()->namespace;
$all_models = [
	'core\classes\models\Country' => [
		'core\classes\models\data\Country',
		'sites\\'.$namespace.'\classes\models\data\Country',
	],
	'core\classes\models\State' => [
		'core\classes\models\data\State',
		'sites\\'.$namespace.'\classes\models\data\State',
	],
	'core\classes\models\City' => [
		'core\classes\models\data\City',
		'sites\\'.$namespace.'\classes\models\data\City',
	],
];

$model = new Model($config, $database);
$models = $model->getSiteModels();

foreach ($models as $model_class) {
	if (!isset($all_models[$model_class])) {
		$model_datas = [];

		// get data in the models namespace
		if (preg_match('/^(.*)\\\\(\w+)$/', $model_class, $matches)) {
			$model_datas[] = $matches[1].'\data\\'.$matches[2];
		}

		// get data in the sites namespace
		if (preg_match('/^core\\\\/', $model_class)) {
			$model_datas[] = preg_replace('/^core\\\\/', 'sites\\'.$namespace.'\\', $matches)[0];
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
