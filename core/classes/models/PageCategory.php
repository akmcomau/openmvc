<?php

namespace core\classes\models;

use core\classes\Model;

class PageCategory extends Category {

	protected $link_type   = 'link-table';

	protected $table       = 'page_category';
	protected $primary_key = 'page_category_id';
	protected $columns     = [
		'page_category_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'site_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'page_category_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'page_category_parent_id' => [
			'data_type'      => 'int',
			'null_allowed'   => TRUE,
		],
	];

	protected $indexes = [
		'site_id',
		'page_category_parent_id',
	];

	protected $foreign_keys = [
		'page_category_parent_id' => ['page_category', 'page_category_id'],
	];
}
