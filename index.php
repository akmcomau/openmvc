<?php

use core\classes\exceptions\RedirectException;
use core\classes\AutoLoader;
use core\classes\Config;
use core\classes\Database;
use core\classes\Dispatcher;
use core\classes\Logger;
use core\classes\Request;

$script_start = microtime(TRUE);
define('DS', DIRECTORY_SEPARATOR);

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

try {
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
	$request    = new Request($config, $database);
	$dispatcher = new Dispatcher($config, $database);
	$response   = $dispatcher->dispatch($request);

	$logger->info('Start Request: '.json_encode($request->request_params));

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
	$logger->error("Error during dispatch: $ex");
	?>
	<div style="border: 3px solid red; padding: 10px; background-color: pink;">
		<div style="color: red; font-size: 22px; font-weight: bold;">FATAL ERROR:</div>
		<pre style="white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;"><?php echo $ex; ?></pre>
	</div>
	<?php
}
