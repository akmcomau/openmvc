<?php

/**
 * Minimal $_CONFIG fixture for the framework test suite. Everything not
 * set here is inherited from core/config/default_config.php - notably the
 * top-level 'database' block (engine => 'none', so core/classes/Database
 * never opens a real connection - see Database::__construct()) and the
 * 'default_site' block (records_per_page, num_pagination_links, locale,
 * theme, namespace, etc.) - see core/classes/Config::__construct(). This
 * config is always safe to use: no real database, mail server, or
 * credentials involved.
 */
$_CONFIG = [
	'sites' => [
		'test.local' => [
			'site_id' => 1,
			'force_www_subdomain' => false,
		],
	],
];
