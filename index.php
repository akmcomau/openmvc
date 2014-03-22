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
	$ip = $_SERVER['REMOTE_ADDR'].(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? ' FORWARDED: '.$_SERVER['HTTP_X_FORWARDED_FOR'] : '');
	$logger->info('Start Request ['.$ip.'] '.($config->is_robot ? ' [ROBOT]' : '').': '.$config->getSiteDomain().' => '.json_encode($_GET));

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

	// set the sites domain
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
	header('Location: http://'.$ex->getDomain().$url->getUrl($controller, $method, $params));
}
catch (Exception $ex) {
	log_display_exception($display_errors, $logger, $ex);
}
