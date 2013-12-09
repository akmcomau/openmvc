<?php

$_MENU = [
	'template' => 'menus/public_main.php',
	'ul_class' => 'nav navbar-nav main-menu',
	'a_class' => 'menu-item',
	'menu' => [
		'home' => [
			'controller' => 'Root',
			'method' => 'index',
		],
		'about_us' => [
			'controller' => 'Root',
			'method' => 'page/about_us',
		],
		'contact_us' => [
			'controller' => 'Root',
			'method' => 'page/contact_us',
		],
	],
];
