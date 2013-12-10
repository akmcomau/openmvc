<?php

namespace core\classes\models;

use core\classes\Model;

class Category extends Model {

	protected $link_type = ''; // either 'foreign-key' or 'link-table'
	protected $link_from = ''; // table this is linked from via foreign key

	protected $children = [];

	public function addChild(Category $category) {
		$this->children[] = $category;
	}

	public function getAllByParent($site_id = NULL) {
		if (!$site_id) $site_id = $this->config->siteConfig()->site_id;
		if (is_array($site_id)) $site_id = ['type'=>'in', 'value'=>$site_id];
		$params = ['site_id' => $site_id];

		$categories = $this->getMulti($params, ['name' => 'asc']);
		$categ_data = [];
		foreach ($categories as $category) {
			$categ_data[$category->parent_id][] = [
				'id'     => $category->id,
				'name'   => $category->name,
				'parent' => $category->parent_id,
			];
		}
		return $categ_data;
	}

	public function delete() {
		// remove all link records
		if ($this->link_type == 'link-table') {
			$sql = "DELETE FROM ".$this->table."_link WHERE ".$this->primary_key."=".$this->id;
		}
		elseif ($this->link_type == 'foreign-key') {
			$sql = "UPDATE ".$this->link_from." SET ".$this->table."_id=NULL WHERE ".$this->primary_key."=".$this->id;
		}
		$this->database->executeQuery($sql);

		parent::delete();
	}

	public function getAsOptions($site_id = NULL) {
		if (!$site_id) $site_id = $this->config->siteConfig()->site_id;
		if (is_array($site_id)) $site_id = ['type'=>'in', 'value'=>$site_id];
		$params = ['site_id' => $site_id];
		$categories = $this->getMulti($params, ['name' => 'asc']);
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

	public function getAsMenuArray($controller, $method, $li_class = '', $site_id = NULL) {
		if (!$site_id) $site_id = $this->config->siteConfig()->site_id;
		if (is_array($site_id)) $site_id = ['type'=>'in', 'value'=>$site_id];
		$params = ['site_id' => $site_id];
		$categories = $this->getMulti($params, ['name' => 'asc']);
		$by_parent = [];
		$by_id = [];
		foreach ($categories as $category) {
			$by_parent[$category->parent_id][] = [
				'controller' => $controller,
				'method'     => $method,
				'params'     => [$category->id, $category->getCanonicalName()],
				'text'       => $category->name,
				'class'      => $li_class,
			];
			$by_id[$category->id] = &$by_parent[$category->parent_id][count($by_parent[$category->parent_id])-1];
		}

		foreach ($by_parent as $parent_id => &$categ) {
			if ($parent_id != '') {
				$by_id[$parent_id]['children'] = $categ;
			}
		}

		if (isset($by_parent[NULL])) {
			return $by_parent[NULL];
		}
		return [];
	}

	public function getCanonicalName() {
		return str_replace(' ', '-', strtolower($this->name));
	}

	public function hasImage() {
		return FALSE;
	}

	public function getImage() {
		return NULL;
	}
}
