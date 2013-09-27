<?php

namespace core\classes\models;

use core\classes\Model;

class Customer extends Model {

	protected $table       = 'customer';
	protected $primary_key = 'customer_id';
	protected $columns     = [
		'customer_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'customer_created' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'site_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'customer_active' => [
			'data_type'      => 'bool',
			'null_allowed'   => FALSE,
			'default_value'  => 'TRUE',
		],
		'customer_type' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
			'default_value'  => CUSTOMER_TYPE_NORMAL,
		],
		'customer_login' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => FALSE,
		],
		'customer_password' => [
			'data_type'      => 'text',
			'data_length'    => '64',
			'null_allowed'   => FALSE,
		],
		'subscription_type_id' => [
			'data_type'      => 'int',
			'null_allowed'   => TRUE,
		],
		'customer_subscription_expire' => [
			'data_type'      => 'datetime',
			'null_allowed'   => TRUE,
		],
		'customer_first_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'customer_last_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'customer_email' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'customer_email_verified' => [
			'data_type'      => 'bool',
			'null_allowed'   => FALSE,
			'default_value'  => 'FALSE',
		],
		'customer_phone' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => TRUE,
		],
		'customer_token' => [
			'data_type'      => 'text',
			'data_length'    => '64',
			'null_allowed'   => TRUE,
		],
		'customer_token_created' => [
			'data_type'      => 'datetime',
			'null_allowed'   => TRUE,
		],
	];

	protected $indexes = [
		'site_id',
		'customer_active',
		'customer_type',
		'customer_token',
		'customer_token_created',
		'subscription_type_id',
		'customer_subscription_expire',
	];

	protected $uniques = [
		['site_id', 'customer_login'],
		['site_id', 'customer_email'],
	];

	protected $foreign_keys = [
		'subscription_type_id' => ['subscription_type', 'subscription_type_id'],
	];

}
