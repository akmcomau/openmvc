<?php

$_URLS = [
	'aliases' => ['en' => 'root'],
	'methods' => [
		'index' => [
			'link_text' => ['en' => 'Home'],
			'meta_tags' => ['title' => $this->config->siteConfig()->name],
		],
		'terms' => [
			'aliases' => ['en' => 'terms-and-conditions'],
			'link_text' => ['en' => 'Terms'],
		],
		'privacy' => [
			'aliases' => ['en' => 'privacy-policy'],
			'link_text' => ['en' => 'Privacy'],
		],
		'aboutUs' => [
			'aliases' => ['en' => 'about-us'],
			'link_text' => ['en' => 'About Us'],
		],
		'contactUs' => [
			'aliases' => ['en' => 'contact-us'],
			'link_text' => ['en' => 'Contact Us'],
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