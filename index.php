<?php

if (!(isset($_SERVER['no_session']) && $_SERVER['no_session'])) {
	session_start();
}

use core\classes\exceptions\DomainRedirectException;
use core\classes\exceptions\RedirectException;
use core\classes\AutoLoader;
use core\classes\Config;
use core\classes\Database;
use core\classes\Dispatcher;
use core\classes\Logger;
use core\classes\Request;
use core\classes\URL;

include('core/ErrorHandler.php');
include('core/Robots.php');
include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();
$logger = Logger::getLogger('');
$config = new Config();

try {
	// is this a bot?
	if (!isset($_SERVER['HTTP_USER_AGENT']) || preg_match($_ROBOTS, $_SERVER['HTTP_USER_AGENT'])) {
		$config->setRobot(TRUE);
	}

	// is this from a campaign
	if (isset($_REQUEST['utm_source'])) {
		$_SESSION['from_campaign'] =
			(isset($_REQUEST['utm_source']) ? $_REQUEST['utm_source'] : '--').' || '.
			(isset($_REQUEST['utm_medium']) ? $_REQUEST['utm_medium'] : '--').' || '.
			(isset($_REQUEST['utm_campaign']) ? $_REQUEST['utm_campaign'] : '--').' || '.
			(isset($_REQUEST['utm_term']) ? $_REQUEST['utm_term'] : '--').' || '.
			(isset($_REQUEST['utm_content']) ? $_REQUEST['utm_content'] : '--');
	}
	else if (isset($_REQUEST['gclid'])) {
		$_SESSION['from_campaign'] = 'Adwords - '.$_REQUEST['gclid'];
	}

	// log the start of the request
	if (!(isset($_GET['no_session']) && $_GET['no_session'])) {
		log_request_start($config, $logger);
	}

	// set the sites domain
	$config->setSiteDomain($_SERVER['HTTP_HOST']);
	$display_errors = $config->siteConfig()->display_errors;

	// clean up log files if required
	if ($config->siteConfig()->logger_clean_enable) {
		try {
			if ($config->siteConfig()->logger_clean_frequency > 0) {
				$rand = rand(1, $config->siteConfig()->logger_clean_frequency);
				if ($rand == 1) {
					$logger->info('Cleaning up log files');
					$path = $config->siteConfig()->logger_clean_path;
					$regex = $config->siteConfig()->logger_clean_regex;
					$ttl = $config->siteConfig()->logger_clean_ttl;
					Logger::clean($path, $regex, $ttl);
				}
			}
		}
		catch (Exception $ex) {
			$logger->error("Error during log file cleanup: $ex");
		}
	}

	// initialise the request
	$request    = new Request($config);
	$dispatcher = new Dispatcher($config);
	$url        = new Url($config);
	$dispatcher->routeRequest($request);

	// create the database object
	$database   = new Database(
		$config,
		$url->getOnlyMasterDB($url->getControllerClassName($request->getControllerClass()), $request->getMethodName())
	);
	$request->setDatabase($database);
	$dispatcher->setDatabase($database);

	// run the before_request hook
	Dispatcher::beforeRequest($config, $database, $request);

	$response = $dispatcher->dispatchRequest($request);

	$response->sendHeaders();
	$response->sendContent();
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
	header('Location: '.$url->getUrl($controller, $method, $params));
}
catch (Exception $ex) {
	log_display_exception($display_errors, $logger, $ex);
}
