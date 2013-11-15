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
		'page/contact_us' => [
			'aliases' => ['en' => 'contact-us'],
			'link_text' => ['en' => 'Contact Us'],
			'language' => ['contact_us.php'],
			'data' => ['form' => 'contactUsForm'],
		],
		'page/contact_us_sent' => [
			'aliases' => ['en' => 'contact-us-sent'],
			'language' => ['contact_us.php'],
		],
		'contactUsSend' => [
			'aliases' => ['en' => 'contact-us-send'],
		],
		'error_401' => [
			'aliases' => ['en' => 'error-401']
		],
		'error_404' => [
			'aliases' => ['en' => 'error-404']
		],
		'error_500' => [
			'aliases' => ['en' => 'error-500']
		],
	],
];