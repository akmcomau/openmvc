<?php

$_MENU = [
	'public_site' => [
		'controller' => 'Root',
		'method' => 'index',
		'text' => $this->language->get('public_site'),
		'icon' => 'icon-home',
	],
	'administrator' => [
		'url' => 'javascript:;',
		'text' => $this->authentication->administratorLoggedIn()['administrator_name'],
		'icon' => 'icon-user',
		'children' => [
			'profile' => [
				'controller' => 'administrator/Administrators',
				'method' => 'edit',
				'text' => $this->language->get('profile'),
				'params' => [$this->authentication->administratorLoggedIn()['administrator_id']],
			],
			'logout' => [
				'controller' => 'Administrator',
				'method' => 'logout',
			],
		],
	],
];
