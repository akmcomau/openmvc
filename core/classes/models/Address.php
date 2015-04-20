<?php

namespace core\classes\models;

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
			'null_allowed'   => TRUE,
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
		'city_id',
		'state_id',
		'country_id',
	];
	protected $foreign_keys = [
		'customer_id'  => ['customer', 'customer_id'],
		'city_id'      => ['city',     'city_id'],
		'state_id'     => ['state',    'state_id'],
		'country_id'   => ['country',  'country_id'],
	];


	public function getCountry() {
		if (isset($this->objects['country'])) {
			return $this->objects['country'];
		}

		$this->objects['country'] = $this->getModel('\core\classes\models\Country')->get([
			'id' => $this->country_id,
		]);
		return $this->objects['country'];
	}

	public function getState() {
		if (isset($this->objects['state'])) {
			return $this->objects['state'];
		}

		$this->objects['state'] = $this->getModel('\core\classes\models\State')->get([
			'id' => $this->state_id,
		]);
		return $this->objects['state'];
	}

	public function getCity() {
		if (isset($this->objects['city'])) {
			return $this->objects['city'];
		}

		$this->objects['city'] = $this->getModel('\core\classes\models\City')->get([
			'id' => $this->city_id,
		]);
		return $this->objects['city'];
	}
}
