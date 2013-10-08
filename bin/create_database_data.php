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
$all_models = [];
$sites = $config->sites;
foreach ($sites as $domain => $data) {
	$config = new Config();
	$config->setSiteDomain('www.'.$domain);
	$model = new Model($config, $database);
	$models = $model->listAllModels();

	foreach ($models as $model_class) {
		if (!isset($all_models[$model_class])) {
			$data_class = preg_replace('/\\\\([\w]+)$/', '\\\\data\\\\$1', $model_class);
			$all_models[$model_class] = $data_class;
		}
	}
}

// create the data
foreach ($all_models as $model_class => $data_class) {
	try {
		$data_model = $model->getModel($data_class);
		$records    = $data_model->getRecords();
		print $model_class."\n";

		foreach ($records as $record) {
			$object = $model->getModel($model_class);
			foreach ($record as $property => $value) {
				$object->$property = $value;
			}
			$object->insert();
		}
	}
	catch (AutoLoaderException $ex) {}
}
