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
$logger = Logger::getLogger('');
$config = new Config();

$root_path = realpath(__DIR__.DS.'..');

$logger->info("Setting default permissions on dir: $root_path");
$cmd = "sudo chown -R ".$_SERVER['USER'].":www-data $root_path";
system($cmd);
$cmd = "sudo find $root_path -type f -exec chmod 644 {} \\;";
system($cmd);
$cmd = "sudo find $root_path -type d -exec chmod 755 {} \\;";
system($cmd);
$cmd = "sudo chmod 664 $root_path/core/config/config.php";
system($cmd);

$logger->info("Fixing permissions on dir: $root_path/bin");
$cmd = "sudo find $root_path/bin/ -type f -exec chmod 755 {} \\;";
system($cmd);

$logger->info("Fixing permissions on dir: $root_path/logs");
$cmd = "sudo chmod 770 $root_path/logs";
system($cmd);
$cmd = "sudo find $root_path/logs/ -type f -exec chmod 660 {} \\;";
system($cmd);

$base_site_path = $root_path.DS.'sites'.DS;
$logger->info("Fixing permissions on dir: $base_site_path");
$cmd = "sudo chgrp -R www-data $base_site_path";
system($cmd);
$cmd = "sudo find $base_site_path -type f -exec chmod 664 {} \\;";
system($cmd);
$cmd = "sudo find $base_site_path -type d -exec chmod 775 {} \\;";
system($cmd);
