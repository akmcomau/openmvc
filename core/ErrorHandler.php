<?php

use core\classes\Dispatcher;
use core\classes\URL;
use core\classes\Request;
use core\classes\Model;
use core\classes\Authentication;

$script_start = microtime(TRUE);
$suppress_exceptions = NULL;
$display_errors = TRUE;

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
		$response = $dispatcher->dispatchRequest($request);

		$response->sendHeaders();
		$response->sendContent();
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
	global $config;
	global $database;

	$logger = Logger::getLogger('');
	$error = error_get_last();
	if ($error) {
		$ex = new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
		log_display_exception($display_errors, $logger, $ex);
	}
	$script_time = number_format(microtime(TRUE) - $script_start, 6);
	if (php_sapi_name() == 'cli') {
		$logger->debug("End Request: $script_time");
	}
	else {
		$logger->info("End Request: $script_time");
	}

	if (!$config->is_robot && $config->siteConfig()->enable_analytics && isset($_SESSION['db_session_id'])) {

		// do not want to log admin requests
		$request    = new Request($config, $database);
		$auth = new Authentication($config, $database, $request);
		if (!$auth->getAdministratorID()) {
			// update the session record
			$model = new Model($config, $database);
			$session = $model->getModel('\core\classes\models\Session')->get(['id' => $_SESSION['db_session_id']]);
			if ($session) {
				$session->end = date('c');
				$session->duration = time() - strtotime($session->start);
				$session->pages_viewed++;
				$session->update();

				// insert the session_request record
				$session_request = $model->getModel('\core\classes\models\SessionRequest');
				$session_request->session_id = $_SESSION['db_session_id'];
				$session_request->uri = substr($_SERVER['REQUEST_URI'], 0, 255);
				$session_request->time = date('c');
				$session_request->response_time = $script_time;
				$session_request->response_code = http_response_code();
				$session_request->insert();

				// log the referer if not from this domain
				if (isset($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER'])) {
					if (!preg_match('/'.$_SERVER['HTTP_HOST'].'/', $_SERVER['HTTP_REFERER'])) {
						$parse = parse_url($_SERVER['HTTP_REFERER']);
						$session_referer = $model->getModel('\core\classes\models\SessionRequestReferer');
						$session_referer->session_request_id = $session_request->id;
						$session_referer->time = date('c');
						$session_referer->url = $_SERVER['HTTP_REFERER'];
						$session_referer->domain = $parse['host'];
						$session_referer->utm_campaign = isset($_GET['utm_campaign']) ? $_GET['utm_campaign'] : NULL;
						$session_referer->utm_source = isset($_GET['utm_source']) ? $_GET['utm_source'] : NULL;
						$session_referer->utm_medium = isset($_GET['utm_medium']) ? $_GET['utm_medium'] : NULL;
						$session_referer->utm_term = isset($_GET['utm_term']) ? $_GET['utm_term'] : NULL;
						$session_referer->utm_content = isset($_GET['utm_content']) ? $_GET['utm_content'] : NULL;
						$session_referer->insert();
					}
				}
			}
		}
	}
}
set_error_handler("exception_error_handler");
register_shutdown_function("shutdown_error_handler");
