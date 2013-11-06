<?php

$_MENU = [
	'template' => 'menus/public_user.php',
	'ul_class' => 'nav navbar-nav navbar-right main-menu',
	'a_class' => 'menu-item',
	'menu' => [
		'account' => [
			'url' => 'javascript:;',
			'text_tag' => 'my_account',
			'children' => [
				'account_details' => [
					'controller' => 'Customer',
					'method' => 'contact_details',
				],
				'account_password' => [
					'controller' => 'Customer',
					'method' => 'change_password',
				],
			],
		],
		'logout' => [
			'controller' => 'Customer',
			'method' => 'logout',
		],
	],
];
