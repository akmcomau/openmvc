<?php

$_DEFAULT_CONFIG = (object)[
	'default_site' => [
		'email_addresses' => [],
		'name' => 'OpenMVC',
		'namespace' => 'default',
		'layout_class' => '\\core\\classes\\renderable\\Layout',
		'layout_template' => 'layouts/default.php',
		'theme' => 'default',
		'static_prefix' => 'static-1',
		'display_errors' => false,
		'bcrypt_cost' => 10,
		'language' => 'en'
	]
];
