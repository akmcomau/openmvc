<?php

namespace core\classes\models;

use core\classes\Model;

class BlockCategory extends Category {

	protected $link_type   = 'link-table';

	protected $table       = 'block_category';
	protected $primary_key = 'block_category_id';
	protected $columns     = [
		'block_category_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'site_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'block_category_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'block_category_parent_id' => [
			'data_type'      => 'int',
			'null_allowed'   => TRUE,
		],
	];

	protected $indexes = [
		'site_id',
		'block_category_parent_id',
	];

	protected $foreign_keys = [
		'block_category_parent_id' => ['block_category', 'block_category_id'],
	];
}
