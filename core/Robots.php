<?php

if (file_exists(__DIR__.'/config/robots.php')) {
	include(__DIR__.'/config/robots.php');
}
else {
	$_ROBOTS = '/bot|index|spider|crawl|wget|curl|slurp|Mediapartners-Google|Feedfetcher-Google/i';
}
