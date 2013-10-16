<?php

namespace core\classes\models;

use core\classes\Model;

class Category extends Model {

	protected $children = [];

	public function addChild(Category $category) {
		$this->children[] = $category;
	}

	public function getAllByParent() {
		$categories = $this->getMulti(NULL, ['name' => 'asc']);
		$categ_data = [];
		foreach ($categories as $category) {
			$categ_data[$category->parent_id][] = [
				'id'   => $category->id,
				'name' => $category->name,
			];
		}
		return $categ_data;
	}

	public function getAsOptions() {
		$categories = $this->getMulti(NULL, ['name' => 'asc']);
		$by_parent = [];
		$by_id = [];
		foreach ($categories as $category) {
			$by_parent[$category->parent_id][] = [
				'id'     => $category->id,
				'name'   => $category->name,
				'parent' => $category->parent_id,
			];
			$by_id[$category->id] = &$by_parent[$category->parent_id][count($by_parent[$category->parent_id])-1];
		}

		foreach ($by_parent as $parent_id => &$categ) {
			if ($parent_id != '') {
				$by_id[$parent_id]['children'][] = $categ;
			}
		}

		$options = [];
		if (isset($by_parent[NULL])) {
			$this->getAsOptionRecursive($options, $by_parent[NULL]);
		}
		return $options;
	}

	protected function getAsOptionRecursive(&$options, $categories, $level = 0) {
		foreach ($categories as $category) {
			$prefix = '';
			for ($i=0; $i<$level; $i++) {$prefix .= '&nbsp;&nbsp;';}

			$options[$category['id']] = $prefix.$category['name'];
			if (isset($category['children'])) {
				foreach ($category['children'] as $sub_category) {
					$this->getAsOptionRecursive($options, $sub_category, ++$level);
				}
			}
		}
	}
}
