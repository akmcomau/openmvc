<?php

namespace core\classes\models;

use core\classes\Model;

class State extends Model {

	protected $table       = 'state';
	protected $primary_key = 'state_id';
	protected $columns     = [
		'state_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'country_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'state_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
	];
	protected $indexes = [
		'state_name',
		'country_id',
	];
	protected $foreign_keys = [
		'country_id' => ['country', 'country_id'],
	];

}
