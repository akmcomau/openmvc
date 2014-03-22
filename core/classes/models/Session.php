<?php

namespace core\classes\models;

use core\classes\Model;

class Session extends Model {

	protected $table       = 'session';
	protected $primary_key = 'session_id';

	protected $columns     = [
		'session_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'site_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'session_session_id' => [
			'data_type'      => 'text',
			'data_length'    => '64',
			'null_allowed'   => FALSE,
		],
		'session_ip' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => FALSE,
		],
		'session_start' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'session_end' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'session_duration' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'customer_id' => [
			'data_type'      => 'int',
			'null_allowed'   => TRUE,
		],
		'session_pages_viewed' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'session_user_agent' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => FALSE,
		],
		'session_last_session_id' => [
			'data_type'      => 'text',
			'null_allowed'   => TRUE,
		],
		'country_id' => [
			'data_type'      => 'int',
			'null_allowed'   => TRUE,
		],
		'state_id' => [
			'data_type'      => 'int',
			'null_allowed'   => TRUE,
		],
		'city_id' => [
			'data_type'      => 'int',
			'null_allowed'   => TRUE,
		],
		'language' => [
			'data_type'      => 'text',
			'data_length'    => '8',
			'null_allowed'   => TRUE,
		],
		'browser' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => TRUE,
		],
		'browser_version' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => TRUE,
		],
		'operating_system' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => TRUE,
		],
		'mobile_device' => [
			'data_type'      => 'text',
			'data_length'    => '32',
			'null_allowed'   => TRUE,
		],
	];

	protected $indexes = [
		'session_session_id',
		'session_start',
		'session_end',
		'session_pages_viewed',
		'session_duration',
		'session_last_session_id',
		'customer_id',
		'country_id',
		'state_id',
		'city_id',
		'language',
		'browser',
		'operating_system',
		'mobile_device',
	];

	protected $foreign_keys = [
		'state_id'    => ['city',    'city_id'],
		'state_id'    => ['state',   'state_id'],
		'country_id'  => ['country', 'country_id'],
		'customer_id' => ['customer', 'customer_id'],
	];
}
