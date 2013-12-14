#!/usr/bin/env php
<?php

use core\classes\exceptions\AutoLoaderException;

use core\classes\Database;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Model;
use core\classes\URL;
use core\classes\AutoLoader;

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

// get all the models for all sites
// FIXME: Hack to fix ordering issue
$all_models = [
	'core\classes\models\Country' => 'core\classes\models\data\Country',
	'core\classes\models\State' => 'core\classes\models\data\State',
	'core\classes\models\City' => 'core\classes\models\data\City',
];
$sites = $config->sites;
foreach ($sites as $domain => $data) {
	$config = new Config();
	$config->setSiteDomain('www.'.$domain);
	$model = new Model($config, $database);
	$models = $model->getSiteModels();

	foreach ($models as $model_class) {
		if (!isset($all_models[$model_class])) {
			if (preg_match('|^(.*)\\\\(\w+)$|', $model_class, $matches)) {
				$all_models[$model_class] = $matches[1].'\data\\'.$matches[2];
			}
		}
	}
}

// create the data
foreach ($all_models as $model_class => $data_class) {
	if (class_exists($data_class)) {
		$object = $model->getModel($model_class);
		$object->insertInitalData($data_class);
	}
}
