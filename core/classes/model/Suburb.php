<?php

namespace core\classes\model;

use core\classes\Model;

class Suburb extends Model {

	protected $table       = 'suburb';
	protected $primary_key = 'suburb_id';
	protected $columns     = [
		'suburb_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'country_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'state_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'city_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'suburb_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'suburb_postcode' => [
			'data_type'      => 'text',
			'data_length'    => '10',
			'null_allowed'   => FALSE,
		],
	];
	protected $indexes = [
		'suburb_postcode',
		'suburb_name',
		'city_id',
		'state_id',
		'country_id',
	];
	protected $foreign_keys = [
		'city_id'    => ['city',    'city_id'],
		'state_id'   => ['state',   'state_id'],
		'country_id' => ['country', 'country_id'],
	];

}
