#!/usr/bin/env php
<?php

use core\classes\Config;
use core\classes\Logger;
use core\classes\AutoLoader;

include('core/ErrorHandler.php');
include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();

$root_path = __DIR__.DS.'..'.DS;

$git_cmd = $_SERVER['argv'][1];

# update openmvc
$cmd = 'cd '.$root_path.' && git '.$git_cmd.' 2>&1';
print "Updating OpenMVC ($root_path) ...\n";
print "\t$cmd\n";
$output = [];
exec($cmd, $output);
print "\t".join("\n\t", $output)."\n\n";

# update the sites
foreach (glob($root_path.'sites'.DS.'*') as $path) {
	if (is_dir($path)) {
		$cmd = 'cd '.$path.' && git '.$git_cmd.' 2>&1';
		print "Updating Site ($path) ...\n";
		print "\t$cmd\n";
		$output = [];
		exec($cmd, $output);
		print "\t".join("\n\t", $output)."\n\n";
	}
}

# update the modules
foreach (glob($root_path.'modules'.DS.'*') as $path) {
	if (is_dir($path)) {
		$cmd = 'cd '.$path.' && git '.$git_cmd.' 2>&1';
		print "Updating module ($path) ...\n";
		print "\t$cmd\n";
		$output = [];
		exec($cmd, $output);
		print "\t".join("\n\t", $output)."\n\n";
	}
}