<?php

namespace core\classes\model;

use core\classes\Model;

class Address extends Model {

	protected $table       = 'address';
	protected $primary_key = 'address_id';
	protected $columns     = [
		'address_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'address_created' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'address_active' => [
			'data_type'      => 'bool',
			'null_allowed'   => FALSE,
			'default_value'  => 'TRUE',
		],
		'address_default' => [
			'data_type'      => 'bool',
			'null_allowed'   => FALSE,
			'default_value'  => 'FALSE',
		],
		'customer_id' => [
			'data_type'      => 'bigint',
			'null_allowed'   => FALSE,
		],
		'address_first_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'address_last_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'address_line1' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'address_line2' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'address_postcode' => [
			'data_type'      => 'text',
			'data_length'    => '10',
			'null_allowed'   => FALSE,
		],
		'suburb_id' => [
			'data_type'      => 'bigint',
			'null_allowed'   => FALSE,
		],
		'city_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'state_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'country_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
	];
	protected $indexes = [
		'customer_id',
		'address_active',
		'address_postcode',
		'suburb_id',
		'city_id',
		'state_id',
		'country_id',
	];
	protected $foreign_keys = [
		'customer_id'  => ['customer', 'customer_id'],
		'suburb_id'    => ['suburb',   'suburb_id'],
		'city_id'      => ['city',     'city_id'],
		'state_id'     => ['state',    'state_id'],
		'country_id'   => ['country',  'country_id'],
	];

}
