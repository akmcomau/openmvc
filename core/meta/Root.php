<?php

$_URLS = [
	'aliases' => ['en' => 'root'],
	'methods' => [
		'index' => [
			'link_text' => ['en' => 'Home'],
			'meta_tags' => ['title' => ['en' => $this->config->siteConfig()->name]],
		],
		'page/terms' => [
			'aliases' => ['en' => 'terms-and-conditions'],
			'link_text' => ['en' => 'Terms'],
		],
		'page/privacy' => [
			'aliases' => ['en' => 'privacy-policy'],
			'link_text' => ['en' => 'Privacy'],
		],
		'page/about_us' => [
			'aliases' => ['en' => 'about-us'],
			'link_text' => ['en' => 'About Us'],
		],
		'contactUs' => [
			'aliases' => ['en' => 'contact-us'],
			'link_text' => ['en' => 'Contact Us'],
		],
		'contactUsSent' => [
			'aliases' => ['en' => 'contact-us-sent'],
			'language' => ['contact_us.php'],
		],
		'contactUsSend' => [
			'aliases' => ['en' => 'contact-us-send'],
		],
		'error401' => [
			'aliases' => ['en' => 'error-401']
		],
		'error404' => [
			'aliases' => ['en' => 'error-404']
		],
		'error500' => [
			'aliases' => ['en' => 'error-500']
		],
	],
];