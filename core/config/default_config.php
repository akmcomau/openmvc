<?php

$_DEFAULT_CONFIG = (object)[
	'modules' => [],
	'default_site' => [
		'email_addresses' => [
        	'from' => 'info@example.com',
	        'contact_us' => 'info@example.com',
		],
		'default_site' => false,
		'enable_ssl' => false,
		'default_block_type' => 'HTML',
		'modules' => [],
		'tmp_path' => '/tmp',
		'secret' => 'ABCDEFGHIJK1234567891234',
		'name' => 'OpenMVC',
		'namespace' => 'default',
		'default_layout_class' => '\\core\\classes\\renderable\\Layout',
		'default_layout_template' => 'layouts/default.php',
		'admin_layout_class' => '\\core\\classes\\renderable\\Layout',
		'admin_layout_template' => 'layouts/admin.php',
		'theme' => 'default',
		'static_prefix' => '/static-1',
		'og_meta_tags' => true,
		'og_type' => 'website',
		'load_default_language_files' => false,
		'enable_analytics' => true,
		'enable_analytics_ecommerce' => true,
		'enable_latex' => false,
		'enable_public' => true,
		'enable_admin' => true,
		'display_errors' => false,
		'site_offline_mode' => false,
		'site_maintenance_mode' => false,
		'post_login_redirect' => false,
		'page_div_class' => 'container',
		'bcrypt_cost' => 10,
		'locale' => 'en_AU.utf8',
		'language' => 'en',
		'records_per_page' => 20,
		'num_pagination_links' => 11,
		'contact_fields' => [
			'name' => ['type'=>'string', 'message_text_tag' => 'error_name'],
			'email' => ['type'=>'email', 'message_text_tag' => 'error_email'],
			'enquiry' => ['type'=>'string', 'message_text_tag' => 'error_enquiry'],
		],
	]
];
