<?php

namespace core\classes\models;

use core\classes\Model;

class Administrator extends Model {

	protected $table       = 'administrator';
	protected $primary_key = 'administrator_id';
	protected $columns     = [
		'administrator_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'administrator_created' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'administrator_active' => [
			'data_type'      => 'bool',
			'null_allowed'   => FALSE,
			'default_value'  => 'TRUE',
		],
		'administrator_type' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
			'default_value'  => ADMINISTRATOR_TYPE_SUPER,
		],
		'administrator_login' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => FALSE,
		],
		'administrator_password' => [
			'data_type'      => 'text',
			'data_length'    => '64',
			'null_allowed'   => FALSE,
		],
		'administrator_first_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'administrator_last_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'administrator_email' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'administrator_phone' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => TRUE,
		],
		'administrator_token' => [
			'data_type'      => 'text',
			'data_length'    => '64',
			'null_allowed'   => FALSE,
		],
		'administrator_token_created' => [
			'data_type'      => 'datetime',
			'null_allowed'   => TRUE,
		],
	];

	protected $indexes = [
		'administrator_active',
		'administrator_type',
		'administrator_token',
		'administrator_token_created',
	];

	protected $uniques = [
		'administrator_login',
		'administrator_email',
	];

}
