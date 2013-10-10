<?php

$_DEFAULT_CONFIG = (object)[
	'modules' => [],
	'default_site' => [
		'email_addresses' => [],
		'modules' => [],
		'name' => 'OpenMVC',
		'namespace' => 'default',
		'default_layout_class' => '\\core\\classes\\renderable\\Layout',
		'default_layout_template' => 'layouts/default.php',
		'admin_layout_class' => '\\core\\classes\\renderable\\Layout',
		'admin_layout_template' => 'layouts/admin.php',
		'theme' => 'default',
		'static_prefix' => 'static-1',
		'enable_public' => true,
		'enable_admin' => true,
		'display_errors' => false,
		'site_offline_mode' => false,
		'site_maintenance_mode' => false,
		'bcrypt_cost' => 10,
		'language' => 'en',
		'records_per_page' => 20,
		'num_pagination_links' => 11
	]
];
