#!/usr/bin/env php
<?php

use core\classes\Config;
use core\classes\Logger;
use core\classes\AutoLoader;

include('core/ErrorHandler.php');
include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();

$logger     = Logger::getLogger('');
$config     = new Config();

$env = [
	'PGHOST'     => $config->database->hostname,
	'PGDATABASE' => $config->database->database,
	'PGUSER'     => $config->database->username,
	'PGPASSWORD' => $config->database->password,
	'PGSCHEMA'   => 'public',
];

foreach ($env as $var => $value) {
	echo "$var=\"$value\"\n";
}