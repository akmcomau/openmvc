<?php

session_start();

use core\classes\exceptions\RedirectException;
use core\classes\AutoLoader;
use core\classes\Config;
use core\classes\Database;
use core\classes\Dispatcher;
use core\classes\Logger;
use core\classes\Request;

$display_errors = TRUE;
$script_start = microtime(TRUE);

function log_display_exception($display_error, $logger, $ex) {
	$logger->error("Error during dispatch: $ex");
	if ($display_error) {
		?>
		<div style="border: 3px solid red; padding: 10px; background-color: pink;">
			<div style="color: red; font-size: 22px; font-weight: bold;">FATAL ERROR:</div>
			<pre style="white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;"><?php echo $ex; ?></pre>
		</div>
		<?php
	}
	else {
		header("Location: /Error/error-500");
	}
}
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
function shutdown_error_handler() {
	global $display_errors;
	$error = error_get_last();
	if ($error) {
		$ex = new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
		$logger = Logger::getLogger('');
		log_display_exception($display_errors, $logger, $ex);
	}
}
set_error_handler("exception_error_handler");
register_shutdown_function("shutdown_error_handler");

include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();
$logger = Logger::getLogger('');
$config = new Config();

$config->setSiteDomain($_SERVER['HTTP_HOST']);
$display_errors = $config->getSiteParams()->display_errors;

try {
	$database   = new Database(
		$config->database->engine,
		$config->database->hostname,
		$config->database->username,
		$config->database->database,
		$config->database->password
	);
	$request    = new Request($config, $database);
	$logger->info('Start Request: '.json_encode($request->get_params));

	$dispatcher = new Dispatcher($config, $database);
	$response = $dispatcher->dispatch($request);

	$response->sendHeaders();
	$response->sendContent();

	$script_time = number_format(microtime(TRUE) - $script_start, 6);
	$logger->info("End Request: $script_time");
}
catch (RedirectException $ex) {
	$logger->info($ex->getMessage());
	header("Location: {$ex->getURL()}");
}
catch (Exception $ex) {
	log_display_exception($display_errors, $logger, $ex);
}
