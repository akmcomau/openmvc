<?php

if (!(isset($_GET['no_session']) && $_GET['no_session'])) {
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
include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();
$logger = Logger::getLogger('');
$config = new Config();

try {
	// is this a bot?
	if (!isset($_SERVER['HTTP_USER_AGENT']) || preg_match('/bot|index|spider|crawl|wget|curl|slurp|Mediapartners-Google|Feedfetcher-Google/i', $_SERVER['HTTP_USER_AGENT'])) {
		$config->setRobot(TRUE);
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

	$database   = new Database($config);
	$request    = new Request($config, $database);
	$dispatcher = new Dispatcher($config, $database);
	$response = $dispatcher->dispatch($request);

	$response->sendHeaders();
	$response->sendContent();
}
catch (RedirectException $ex) {
	$logger->info($ex->getMessage());
	header("Location: {$ex->getUrl()}");
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
