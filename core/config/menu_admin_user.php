<?php

$_MENU = [
	'template' => 'menus/admin_user.php',
	'ul_class' => 'nav navbar-nav navbar-right',
	'a_class' => '',
	'menu' => [
		'public_site' => [
			'controller' => 'Root',
			'method' => 'index',
			'text_tag' => 'public_site',
			'icon' => 'fa fa-home',
		],
		'administrator' => [
			'url' => 'javascript:;',
			'text' => $this->authentication->administratorLoggedIn() ? $this->authentication->administratorLoggedIn()['administrator_name'] : '',
			'icon' => 'fa fa-user',
			'children' => [
				'profile' => [
					'controller' => 'administrator/Administrators',
					'method' => 'edit',
					'text_tag' => 'profile',
					'params' => [$this->authentication->administratorLoggedIn() ? $this->authentication->administratorLoggedIn()['administrator_id'] : ''],
				],
				'logout' => [
					'controller' => 'Administrator',
					'method' => 'logout',
				],
			],
		],
	],
];
