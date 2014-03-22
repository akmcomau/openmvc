<?php

namespace core\classes\models;

use core\classes\Model;

class SessionRequest extends Model {

	protected $table       = 'session_request';
	protected $primary_key = 'session_request_id';

	protected $columns     = [
		'session_request_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'session_id' => [
			'data_type'      => 'bigint',
			'null_allowed'   => FALSE,
		],
		'session_request_uri' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => FALSE,
		],
		'session_request_time' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'session_request_response_time' => [
			'data_type'      => 'numeric',
			'data_length'    => [3, 6],
			'null_allowed'   => FALSE,
		],
		'session_request_response_code' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
	];

	protected $indexes = [
		'session_id',
		'session_request_time',
		'session_request_response_time',
		'session_request_response_code',
	];

	protected $foreign_keys = [
		'session_id'   => ['session',    'session_id'],
	];
}
