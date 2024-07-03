<?php

$_MENU = [
	'template' => 'menus/public_main.php',
	'ul_class' => 'nav navbar-nav navbar-left main-menu',
	'a_class' => 'menu-item',
	'menu' => [
		'home' => [
			'controller' => 'Root',
			'method' => 'index',
			'icon' => 'fa fa-th-large',
		],
		'about_us' => [
			'controller' => 'Root',
			'method' => 'page/about_us',
			'icon' => 'fa fa-th-large',
		],
		'contact_us' => [
			'controller' => 'Root',
			'method' => 'contactUs',
			'icon' => 'fa fa-th-large',
		],
	],
];
