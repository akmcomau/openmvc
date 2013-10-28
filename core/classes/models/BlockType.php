<?php

namespace core\classes\models;

use core\classes\Model;

class BlockType extends Model {

	protected $table       = 'block_type';
	protected $primary_key = 'block_type_id';
	protected $columns     = [
		'block_type_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'block_type_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
	];

	protected $uniques = [
		['block_type_name'],
	];

	public function getAsOptions() {
		$options = [];
		$types = $this->getMulti(NULL, ['name' => 'asc']);
		foreach ($types as $type) {
			$options[$type->id] = $type->name;
		}
		return $options;
	}
}
