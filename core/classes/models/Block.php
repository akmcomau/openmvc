<?php

namespace core\classes\models;

use core\classes\Model;
use core\classes\Module;

class Block extends Model {

	protected $table       = 'block';
	protected $primary_key = 'block_id';
	protected $columns     = [
		'block_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'site_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'block_type_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
		'block_tag' => [
			'data_type'      => 'text',
			'data_length'    => 64,
			'null_allowed'   => FALSE,
		],
		'block_title' => [
			'data_type'      => 'text',
			'data_length'    => 64,
			'null_allowed'   => FALSE,
		],
		'block_content' => [
			'data_type'      => 'text',
			'null_allowed'   => FALSE,
		],
	];

	protected $indexes = [
		'site_id',
		'block_type_id',
		'block_tag',
	];

	protected $uniques = [
		'block_tag',
	];

	protected $foreign_keys = [
		'block_type_id' => ['block_type', 'block_type_id'],
	];

	protected $relationships = [
		'block_category' => [
			'where_fields'  => ['block_category_id'],
			'join_clause'   => 'LEFT JOIN block_category_link USING (block_id) LEFT JOIN block_category USING (block_category_id)',
		],
	];

	public function render() {
		$modules = (new Module($this->config))->getEnabledModules();
		foreach ($modules as $module) {
			if (isset($module['hooks']['models']['block_render'])) {
				$class = $module['namespace'].'\\'.$module['hooks']['models']['block_render'];
				$class = new $class($this->config, $this->database, NULL);
				return call_user_func_array(array($class, 'block_render'), [$this]);
			}
		}

		return $this->content;
	}

	public function setCategory(BlockCategory $category = NULL) {
		$this->objects['category'] = $category;
	}

	public function getCategoryName() {
		$category = $this->getCategory();
		return $category ? $category->name : NULL;
	}

	public function getCategory() {
		// object is not in the database
		if (!$this->id) {
			return NULL;
		}

		if (!isset($this->objects['category'])) {
			$sql = "
				SELECT block_category.*
				FROM
					block_category_link
					JOIN block_category USING (block_category_id)
				WHERE
					block_id=".$this->database->quote($this->id)."
			";
			$record = $this->database->querySingle($sql);
			if ($record) {
				 $this->objects['category'] = $this->getModel('\\core\\classes\\models\\BlockCategory', $record);
			}
			else {
				$this->objects['category'] =  NULL;
			}
		}
		return $this->objects['category'];
	}

	public function insert() {
		// update the block
		parent::insert();

		if (isset($this->objects['category']) && $this->objects['category']) {
			// insert the category
			$link = $this->getModel('\\core\\classes\\models\\BlockCategoryLink');
			$link->block_id = $this->id;
			$link->block_category_id = $this->objects['category']->id;
			$link->insert();
		}
	}

	public function update() {
		// update the block
		parent::update();

		// get the link
		$link = $this->getModel('\\core\\classes\\models\\BlockCategoryLink')->get([
			'block_id' => $this->id,
		]);

		// update the category
		$category = $this->objects['category'];
		if ($category && $link) {
			// update the category
			$link->block_category_id = $category->id;
			$link->update();
		}
		elseif ($category && !$link) {
			// insert the category
			$link = $this->getModel('\\core\\classes\\models\\BlockCategoryLink');
			$link->block_id = $this->id;
			$link->block_category_id = $category->id;
			$link->insert();
		}
		elseif (!$category && $link) {
			// remove the link
			$link->delete();
		}
	}

	public function delete() {
		// delete all block_category_link
		$sql = "DELETE FROM block_category_link WHERE block_id=".$this->database->quote($this->id);
		$this->database->executeQuery($sql);

		parent::delete();
	}
}
