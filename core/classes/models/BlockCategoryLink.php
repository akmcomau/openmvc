<?php

namespace core\classes\models;

use core\classes\Model;

class BlockCategoryLink extends Model {

	protected $table       = 'block_category_link';
	protected $primary_key = 'block_category_link_id';
	protected $columns     = [
		'block_category_link_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'block_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'block_category_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
	];

	protected $indexes = [
		'block_id',
		'block_category_id',
	];

	protected $foreign_keys = [
		'block_id' => ['block', 'block_id'],
		'block_category_id' => ['block_category', 'block_category_id'],
	];
}
