<?php

$_CONFIG = [
	'database' => [
		'engine' => 'pgsql',
		'hostname' => 'localhost',
		'username' => 'openmvc',
		'database' => 'openmvc',
		'password' => 'secret'
	],
	'sites' => [
		'mvc.local' => [
			'site_id' => 1,
			'email_addresses' => [
				'contact_us' => 'info@mvc.local',
				'errors' => 'errors@mvc.local'
			],
			'display_errors' => true
		],
 		'akm.local' => [
			'site_id' => 2,
			'email_addresses' => [
				'contact_us' => 'info@akm.local',
				'errors' => 'errors@akm.local'
			],
			'name' => 'AKM Computer Services',
			'namespace' => 'akm',
			'display_errors' => true
		],
 		'emt.local' => [
			'site_id' => 3,
			'email_addresses' => [
				'contact_us' => 'info@emt.local',
				'errors' => 'errors@emt.local'
			],
			'name' => 'Expert Math Tutoring',
			'namespace' => 'emt',
			'display_errors' => true
		]
	]
];
