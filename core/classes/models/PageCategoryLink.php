<?php

namespace core\classes\models;

use core\classes\Model;

class PageCategoryLink extends Model {

	protected $table       = 'page_category_link';
	protected $primary_key = 'page_category_link_id';
	protected $columns     = [
		'page_category_link_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'page_controller' => [
			'data_type'      => 'text',
			'data_length'    => 256,
			'null_allowed'   => FALSE,
		],
		'page_method' => [
			'data_type'      => 'text',
			'data_length'    => 64,
			'null_allowed'   => FALSE,
		],
		'page_category_id' => [
			'data_type'      => 'int',
			'null_allowed'   => FALSE,
		],
	];

	protected $indexes = [
		'page_controller',
		'page_method',
		'page_category_id',
	];

	protected $foreign_keys = [
		'page_category_id' => ['page_category', 'page_category_id'],
	];

	public function getPageCategories() {
		$sql = "
			SELECT
				page_controller,
				page_method,
				page_category.*
			FROM
				page_category_link
				JOIN page_category USING (page_category_id)
		";
		$result = $this->database->queryMulti($sql);
		$categories = [];
		foreach ($result as $category) {
			$controller = $category['page_controller'];
			$method = $category['page_method'];
			unset($category['page_controller']);
			unset($category['page_method']);
			$categories[$controller][$method] = $this->getModel('\core\classes\models\PageCategory', $category);
		}

		return $categories;
	}
}
