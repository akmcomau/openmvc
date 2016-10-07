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
		'customer_remember_me' => [
			'data_type'      => 'text',
			'data_length'    => '256',
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
		'customer_remember_me',
		'customer_token',
		'customer_token_created',
	];

	protected $partial_uniques = [
		['customer_active = TRUE', 'site_id', 'customer_login'],
		['customer_active = TRUE', 'site_id', 'customer_email'],
	];

	public function getName() {
		return $this->first_name.' '.$this->last_name;
	}

	public function generateToken() {
		$this->customer_token = md5($this->id.time());
		$this->customer_token_created = date('c');
		$this->update();

		return $this->customer_token;
	}

	public function clearToken() {
		$this->token = NULL;
		$this->token_created = NULL;
		$this->update();
	}

	public function getRememberMeToken() {
		$token = $this->generateRememberMeToken();
		if ($token != $this->remember_me) {
			$this->remember_me = $token;
			if ($this->id) {
				$this->update();
			}
		}
		return $token;
	}

	protected function generateRememberMeToken() {
		return hash('sha512', $this->id.$this->login.$this->password);
	}

	public function insert() {
		$this->getRememberMeToken();
		parent::insert();
	}

	public function update() {
		$this->getRememberMeToken();
		parent::update();
	}
}
