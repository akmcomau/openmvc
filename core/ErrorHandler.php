<?php

use core\classes\Dispatcher;
use core\classes\URL;
use core\classes\Database;
use core\classes\Logger;

$script_start = microtime(TRUE);
$suppress_exceptions = NULL;
$display_errors = TRUE;

function log_request_start($config, $logger) {
	global $argv;

	if (php_sapi_name() == 'cli') {
		$logger->info('Start Script : '.$_SERVER['PHP_SELF'].' => '.json_encode($argv));
	}
	else {
		$ip = $_SERVER['REMOTE_ADDR'].(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? ' FORWARDED: '.$_SERVER['HTTP_X_FORWARDED_FOR'] : '');
		$logger->info('Start Request ['.$ip.'] '.($config->is_robot ? ' [ROBOT]' : '').': '.$_SERVER['REQUEST_URI'].' => '.json_encode($_GET));
	}

	// log the useragent if the session was just created
	if (!isset($_SESSION['created'])) {
		$_SESSION['created'] = date('c');
		$language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'N/A';
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'N/A';
		$logger->info('Language: '.$language.' :: User Agent: '.$user_agent);
	}

	// log the referer if not from this domain
	if (isset($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER'])) {
		if (!preg_match('/'.$_SERVER['HTTP_HOST'].'/', $_SERVER['HTTP_REFERER'])) {
			$logger->info('Referer: '.$_SERVER['HTTP_REFERER']);
		}
	}
}

// $value is a regex of the errors to ignore
function suppress_exceptions($value) {
	global $suppress_exceptions;
	$suppress_exceptions = $value;
}

function log_display_exception($display_error, $logger, $ex) {
	global $config;
	global $database;
	global $request;
	global $response;

	$logger->error("Error during dispatch: $ex");

	http_response_code(500);
	header("HTTP/1.1 500 Internal Server Error");

	$GLOBALS['script-error'] = TRUE;
	$GLOABLS['script-exception'] = $ex;

	if ($display_error && (php_sapi_name() === 'cli')) {
		echo "\n".$ex."\n";
	}
	elseif ($display_error) {
		?>
		<div style="border: 3px solid red; padding: 10px; background-color: pink;">
			<div style="color: red; font-size: 22px; font-weight: bold;">FATAL ERROR:</div>
			<pre style="white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;"><?php echo $ex; ?></pre>
		</div>
		<?php
	}
	else {
		$url = new URL($config);
		$request->setControllerClass($url->getControllerClass('Root'));
		$request->setMethodName('error500');
		$request->setMethodParams([]);

		$dispatcher = new Dispatcher($config, $database);
		$dispatcher->setDatabase($database);
		$response = $dispatcher->dispatchRequest($request);

		$response->sendHeaders();
		$response->sendContent();
	}
}
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	global $suppress_exceptions;
	if (strpos($errstr, 'iconv(): Detected an illegal character in input string') === 0) {
		return;
	}
	if (!$suppress_exceptions || !preg_match($suppress_exceptions, $errstr)) {
		throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
	}
}
function shutdown_error_handler() {
	global $config;
	global $display_errors;
	global $script_start;

	// log/display the error
	$logger = Logger::getLogger('');
	$error = error_get_last();
	if ($error) {
		$ex = new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
		log_display_exception($display_errors, $logger, $ex);
	}

	// get response time
	$script_time = number_format(microtime(TRUE) - $script_start, 6);

	// run the after_request hook
	if ($config) {
		Dispatcher::afterRequest($script_time, Database::$db_time);
	}

	// Log the end of the script
	if (php_sapi_name() == 'cli') {
		$logger->info("End Script: $script_time / DB Time: ".Database::$db_time);
	}
	else {
		$logger->info("End Request: $script_time / DB Time: ".Database::$db_time);
	}
}
set_error_handler("exception_error_handler");
register_shutdown_function("shutdown_error_handler");
