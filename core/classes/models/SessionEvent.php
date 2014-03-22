<?php

namespace core\classes\models;

use core\classes\Model;

class SessionEvent extends Model {

	protected $table       = 'session_event';
	protected $primary_key = 'session_event_id';

	protected $columns     = [
		'session_event_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'session_id' => [
			'data_type'      => 'bigint',
			'null_allowed'   => FALSE,
		],
		'session_event_time' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'session_event_category' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => FALSE,
		],
		'session_event_type' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => FALSE,
		],
		'session_event_sub_type' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => FALSE,
		],
		'session_event_value' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => TRUE,
		],

	];

	protected $indexes = [
		'session_id',
		'session_event_time',
		'session_event_category',
		'session_event_type',
		'session_event_sub_type',
	];

	protected $foreign_keys = [
		'session_id'   => ['session',    'session_id'],
	];
}
