<?php

$_MENU = [
	'template' => 'menus/public_user.php',
	'ul_class' => 'nav navbar-nav navbar-right main-menu',
	'a_class' => 'menu-item',
	'menu' => [
		'register' => [
			'controller' => 'Customer',
			'method' => 'register',
		],
		'login' => [
			'controller' => 'Customer',
			'method' => 'login',
		],
	]
];