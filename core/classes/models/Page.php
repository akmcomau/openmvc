<?php

namespace core\classes\models;

use core\classes\Model;

class Page extends Model {

	protected $table       = 'page';
	protected $primary_key = 'page_id';
	protected $columns     = [
		'page_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'page_controller' => [
			'data_type'      => 'text',
			'data_length'    => 256,
			'null_allowed'   => FALSE,
		],
		'page_method' => [
			'data_type'      => 'text',
			'data_length'    => 64,
			'null_allowed'   => FALSE,
		],
		'page_category_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
	];

	protected $indexes = [
		'page_controller',
		'page_method',
		'page_category_id',
	];

	protected $foreign_keys = [
		'page_category_id' => ['page_category', 'page_category_id'],
	];
}
