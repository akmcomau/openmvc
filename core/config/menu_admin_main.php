<?php

$_MENU = [
	'template' => 'menus/admin_main.php',
	'ul_class' => 'mainnav',
	'a_class' => '',
	'menu' => [
		'home' => [
			'controller' => 'Administrator',
			'method' => 'index',
			'icon' => 'icon-home',
		],
		'content' => [
			'url' => 'javascript:;',
			'text_tag' => 'content',
			'icon' => 'icon-copy',
			'children' => [
				'content_pages' => [
					'controller' => 'administrator/Pages',
					'method' => 'index',
					'children' => [
						'content_pages_list' => [
							'controller' => 'administrator/Pages',
							'method' => 'index',
						],
						'content_pages_add' => [
							'controller' => 'administrator/Pages',
							'method' => 'add',
						],
						'content_pages_categories' => [
							'controller' => 'administrator/CategoryManager',
							'method' => 'page',
						]
					],
				],
				'content_blocks' => [
					'controller' => 'administrator/Blocks',
					'method' => 'index',
					'children' => [
						'content_blocks_list' => [
							'controller' => 'administrator/Blocks',
							'method' => 'index',
						],
						'content_blocks_add' => [
							'controller' => 'administrator/Blocks',
							'method' => 'add',
						],
						'content_blocks_categories' => [
							'controller' => 'administrator/CategoryManager',
							'method' => 'block',
						]
					],
				],
				'modules' => [
					'controller' => 'administrator/Modules',
					'method' => 'index',
				],
				'language_editor' => [
					'controller' => 'administrator/LanguageEditor',
					'method' => 'index',
				],
				'file_manager' => [
					'controller' => 'administrator/FileManager',
					'method' => 'index',
				],
			],
		],
		'users' => [
			'url' => 'javascript:;',
			'text_tag' => 'users',
			'icon' => 'icon-user',
			'children' => [
				'administrators' => [
					'controller' => 'administrator/Administrators',
					'method' => 'index',
					'children' => [
						'administrators_list' => [
							'controller' => 'administrator/Administrators',
							'method' => 'index',
						],
						'administrators_add' => [
							'controller' => 'administrator/Administrators',
							'method' => 'add',
						],
					],
				],
				'customers' => [
					'controller' => 'administrator/Customers',
					'method' => 'index',
					'children' => [
						'customers_list' => [
							'controller' => 'administrator/Customers',
							'method' => 'index',
						],
						'customers_add' => [
							'controller' => 'administrator/Customers',
							'method' => 'add',
						],
					],
				],
			],
		]
	],
];