#!/usr/bin/env php
<?php

use core\classes\Database;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Model;
use core\classes\Module;
use core\classes\AutoLoader;

include('core/ErrorHandler.php');
include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();

$logger     = Logger::getLogger('');
$config     = new Config();
$database   = new Database($config);

// Use assocative arrays to avoid duplicates
$modules = [];
$sites = [];
foreach ($config->sites as $domain => $site) {
	$tag = 'sites_'.str_replace('\\', '_', $site->namespace);
	$sites[$site->namespace] = ' *       @ref openmvc_'.$tag;

	$config->setSiteDomain('www.'.$domain);
	$module = new Module($config);

	foreach ($module->getModules() as $module) {
		$tag = str_replace('\\', '_', $module['namespace']);
		$modules[$module['name']] = ' *       @ref openmvc'.$tag;
	}
}

// turn the arrays into strings
$sites = " *     <div id=\"sites\">\n".join("<br>\n", $sites)."\n *     </div>";
$modules = " *     <div id=\"modules\">\n".join("<br>\n", $modules)."\n *     </div>";

// create the mainpage content
$filename = __DIR__.DS.'..'.DS.'docs'.DS.'src'.DS.'mainpage.dox';
$mainpage = file_get_contents($filename.'.tpl');
$mainpage = str_replace(' * 	   <div id="sites"></div>', $sites, $mainpage);
$mainpage = str_replace(' * 	   <div id="modules"></div>', $modules, $mainpage);

// write out the content
file_put_contents($filename, $mainpage);

