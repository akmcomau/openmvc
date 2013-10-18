<?php

$_MENU = [
	'admin' => [
		'controller' => 'Administrator',
		'method' => 'index',
		'text' => $this->language->get('admin_site'),
		'icon' => 'icon-cog',
	],
	'language' => [
		'controller' => 'administrator/LanguageEditor',
		'method' => 'edit',
		'text' => $this->language->get('admin_language'),
		'icon' => 'icon-copy',
	],
	'edit_page' => [
		'controller' => 'administrator/Pages',
		'method' => 'edit',
		'text' => $this->language->get('admin_edit_page'),
		'icon' => 'icon-anchor',
	],
	'logout' => [
		'controller' => 'Administrator',
		'method' => 'logout',
		'text' => $this->language->get('admin_logout'),
		'icon' => 'icon-user',
	],
];
