<?php

session_start();

use core\classes\exceptions\DomainRedirectException;
use core\classes\exceptions\RedirectException;
use core\classes\AutoLoader;
use core\classes\Config;
use core\classes\Database;
use core\classes\Dispatcher;
use core\classes\Logger;
use core\classes\Request;
use core\classes\URL;

$suppress_exceptions = NULL;
$display_errors = TRUE;
$script_start = microtime(TRUE);

function suppress_exceptions($value) {
	global $suppress_exceptions;
	$suppress_exceptions = $value;
}

function log_display_exception($display_error, $logger, $ex) {
	global $config;
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
		$url = new URL($config);
		header("Location: ".$url->getURL('Root', 'error500'));
	}
}
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	global $suppress_exceptions;
	if (!$suppress_exceptions || !preg_match($suppress_exceptions, $errstr)) {
		throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
	}
}
function shutdown_error_handler() {
	global $display_errors;
	global $script_start;

	$logger = Logger::getLogger('');
	$error = error_get_last();
	if ($error) {
		$ex = new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
		log_display_exception($display_errors, $logger, $ex);
}
	$script_time = number_format(microtime(TRUE) - $script_start, 6);
	$logger->info("End Request: $script_time");
}
set_error_handler("exception_error_handler");
register_shutdown_function("shutdown_error_handler");

include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();
$logger = Logger::getLogger('');
$config = new Config();

try {
	$ip = $_SERVER['REMOTE_ADDR'].(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? ':'.$_SERVER['HTTP_X_FORWARDED_FOR'] : '');
	$logger->info('Start Request ['.$ip.'] ['.session_id().']: '.$config->getSiteDomain().' => '.json_encode($_GET));

	$config->setSiteDomain($_SERVER['HTTP_HOST']);
	$display_errors = $config->siteConfig()->display_errors;

	$database   = new Database(
		$config->database->engine,
		$config->database->hostname,
		$config->database->username,
		$config->database->database,
		$config->database->password
	);
	$request    = new Request($config, $database);

	$dispatcher = new Dispatcher($config, $database);
	$response = $dispatcher->dispatch($request);

	$response->sendHeaders();
	$response->sendContent();
}
catch (RedirectException $ex) {
	$logger->info($ex->getMessage());
	header("Location: {$ex->getURL()}");
}
catch (DomainRedirectException $ex) {
	$config->setSiteDomain($ex->getDomain());
	$url = new URL($config);
	$params = [];
	if (!empty($_GET['params'])) {
		$params = explode('/', $_GET['params']);
	}
	$controller = isset($_GET['controller']) ?  $_GET['controller'] : NULL;
	$method = isset($_GET['method']) ?  $_GET['method'] : NULL;
	header('Location: http://'.$ex->getDomain().$url->getURL($controller, $method, $params));
}
catch (Exception $ex) {
	log_display_exception($display_errors, $logger, $ex);
}
