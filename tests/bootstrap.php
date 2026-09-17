<?php

/**
 * PHPUnit bootstrap for the OpenMVC framework (core/) test suite.
 *
 * This mirrors index.php's own bootstrap sequence (Constants -> AutoLoader
 * -> Logger), but points the framework at throwaway fixture config/constants
 * files instead of a real site's, and configures log4php with a null
 * appender - the real, site-specific log4php config (see
 * core/config/log4php.php in a checked-out site) can include a mail
 * appender on error-level logs, which must never fire from a test run.
 */

require __DIR__.'/vendor/autoload.php';

define('TESTS_ROOT', __DIR__);
define('OPENMVC_ROOT', dirname(__DIR__));

$_SERVER['OPENMVC_CONSTANTS_FILE'] = TESTS_ROOT.'/fixtures/constants.php';
$_SERVER['OPENMVC_CONFIG_FILE']    = TESTS_ROOT.'/fixtures/config.php';

require OPENMVC_ROOT.'/core/Constants.php';

// Deliberately NOT calling core\classes\AutoLoader::init() here: besides
// autoloading core\* classes and log4php, it also globs and includes every
// checked-out site's own classes/Autoloader.php
// (sites/*/classes/Autoloader.php), which registers that site's own,
// independently-versioned Composer dependency tree. In this checkout,
// sites/satvue/composer/vendor bundles its own phpunit/phpunit (9.6), and
// loading its classmap corrupts class resolution for the phpunit/phpunit
// (11.x) this suite runs under - PHPUnit\Framework\TestSuite ends up
// resolving to that older version's file, which is missing methods this
// PHPUnit build expects. A framework-only test run has no business loading
// any site's dependencies anyway, so autoloading is reproduced here
// without that site/module autoloader glob.
spl_autoload_register(function ($class) {
	$file = str_replace('\\', DS, $class);
	$filename = OPENMVC_ROOT.DS.$file.'.php';
	if (file_exists($filename)) {
		include($filename);
	}

	$logger_path = OPENMVC_ROOT.DS.'composer/vendor/apache/log4php/src/main/php/'.$class.'.php';
	if (file_exists($logger_path)) {
		include($logger_path);
	}
});
include(OPENMVC_ROOT.'/composer/vendor/autoload.php');

// A baseline, plain-HTTP request environment - individual tests override
// $_SERVER as needed (e.g. to simulate HTTPS), but should never have to set
// this just to avoid an "Undefined array key" warning from Config::isHttps().
$_SERVER['SERVER_PORT'] = 80;

\Logger::configure([
	'rootLogger' => [
		'appenders' => ['null'],
	],
	'appenders' => [
		'null' => [
			'class' => 'LoggerAppenderNull',
		],
	],
]);
