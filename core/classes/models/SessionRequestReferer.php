<?php

namespace core\classes\models;

use core\classes\Model;

class SessionRequestReferer extends Model {

	protected $table       = 'session_request_referer';
	protected $primary_key = 'session_request_referer_id';

	protected $columns     = [
		'session_request_referer_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'session_request_id' => [
			'data_type'      => 'bigint',
			'null_allowed'   => FALSE,
		],
		'session_request_referer_time' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'session_request_referer_url' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => FALSE,
		],
		'session_request_referer_domain' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => FALSE,
		],
		'session_request_referer_utm_campaign' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => TRUE,
		],
		'session_request_referer_utm_source' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => TRUE,
		],
		'session_request_referer_utm_medium' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => TRUE,
		],
		'session_request_referer_utm_term' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => TRUE,
		],
		'session_request_referer_utm_content' => [
			'data_type'      => 'text',
			'data_length'    => '255',
			'null_allowed'   => TRUE,
		],
	];

	protected $indexes = [
		'session_request_id',
		'session_request_referer_time',
		'session_request_referer_domain',
		'session_request_referer_utm_source',
		'session_request_referer_utm_medium',
	];

	protected $foreign_keys = [
		'session_request_id'   => ['session_request',    'session_request_id'],
	];
}
