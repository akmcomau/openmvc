<?php

namespace core\classes\models;

use core\classes\Model;

class City extends Model {

	protected $cacheable   = TRUE;
	protected $table       = 'city';
	protected $primary_key = 'city_id';
	protected $columns     = [
		'city_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'country_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'state_id' => [
			'data_type'      => 'int',
			'null_allowed'   => TRUE,
		],
		'city_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'city_timezone' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => TRUE,
		],
	];
	protected $indexes = [
		'lower(city_name)',
		'state_id',
		'country_id',
	];
	protected $foreign_keys = [
		'state_id'   => ['state',   'state_id'],
		'country_id' => ['country', 'country_id'],
	];
}
