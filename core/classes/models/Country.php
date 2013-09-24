<?php

namespace core\classes\models;

use core\classes\Model;

class Country extends Model {

	protected $table       = 'country';
	protected $primary_key = 'country_id';
	protected $columns     = [
		'country_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'country_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'country_code' => [
			'data_type'      => 'text',
			'data_length'    => '2',
			'null_allowed'   => FALSE,
		],
	];
	protected $indexes = [
		'country_name',
	];

}
