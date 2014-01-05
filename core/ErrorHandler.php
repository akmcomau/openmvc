<?php

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
		header("Location: ".$url->getUrl('Root', 'error500'));
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
