<?php

$_MENU = [
	'template' => 'menus/admin_panel.php',
	'ul_class' => 'nav navbar-nav navbar-right',
	'a_class' => '',
	'menu' => [
		'admin' => [
			'controller' => 'Administrator',
			'method' => 'index',
			'text_tag' => 'admin_site',
			'icon' => 'icon-cog',
		],
		'language' => [
			'controller' => 'administrator/LanguageEditor',
			'method' => 'edit',
			'text_tag' => 'admin_language',
			'icon' => 'icon-copy',
		],
		'edit_page' => [
			'controller' => 'administrator/Pages',
			'method' => 'edit',
			'text_tag' => 'admin_edit_page',
			'icon' => 'icon-anchor',
		],
		'logout' => [
			'controller' => 'Administrator',
			'method' => 'logout',
			'text_tag' => 'admin_logout',
			'icon' => 'icon-user',
		],
	],
];
