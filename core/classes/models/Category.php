<?php

namespace core\classes\models;;

use core\classes\Menu;
use core\classes\Model;

class Category extends Model {

	protected $link_type = ''; // either 'foreign-key' or 'link-table'
	protected $link_from = ''; // table this is linked from via foreign key

	protected $children = [];

	public function addChild(Category $category) {
		$this->children[] = $category;
	}

	// TODO Make this use getByParent
	public function getAllByParent($site_id = NULL, $by_field = NULL, $field_callback = NULL) {
		if (!$site_id) $site_id = $this->config->siteConfig()->site_id;
		if (is_array($site_id)) $site_id = ['type'=>'in', 'value'=>$site_id];
		$params = ['site_id' => $site_id];

		$categories = $this->getMulti($params, ['name' => 'asc']);

		// if we are getting by field we also need the parents by id
		$parents = [];
		if ($by_field) {
			foreach ($categories as $category) {
				if ($category->parent_id == NULL) {
					$parents[$category->id] = $category;
				}
			}
		}

		$categ_data = [];
		foreach ($categories as $category) {
			$categ = [
				'id'        => $category->id,
				'name'      => $category->name,
				'parent'    => $category->parent_id,
				'image'     => $category->hasImage() ? $category->getImageUrl() : NULL,
				'thumbnail' => $category->hasImage() ? $category->getImageThumbnailUrl() : NULL,
			];
			if ($by_field) {
				$parent_field = $category->parent_id ? $parents[$category->parent_id]->$by_field : NULL;
				$child_field  = $category->$by_field;
				if ($field_callback) {
					$parent_field = $field_callback($parent_field);
					$child_field  = $field_callback($child_field);
				}
				$categ_data[$parent_field][$child_field] = $categ;
			}
			else {
				$categ_data[$category->parent_id][] = $categ;
			}
		}
		return $categ_data;
	}

	public function getByParent($site_id = NULL, $by_field = NULL, $field_callback = NULL) {
		if (!$site_id) $site_id = $this->config->siteConfig()->site_id;
		if (is_array($site_id)) $site_id = ['type'=>'in', 'value'=>$site_id];
		$params = ['site_id' => $site_id];

		$categories = $this->getMulti($params, ['name' => 'asc']);

		// if we are getting by field we also need the parents by id
		$parents = [];
		if ($by_field) {
			foreach ($categories as $category) {
				if ($category->parent_id == NULL) {
					$parents[$category->id] = $category;
				}
			}
		}

		$categ_data = [];
		foreach ($categories as $category) {
			$categ = $category;
			if ($by_field) {
				$parent_field = $category->parent_id ? $parents[$category->parent_id]->$by_field : NULL;
				$child_field  = $category->$by_field;
				if ($field_callback) {
					$parent_field = $field_callback($parent_field);
					$child_field  = $field_callback($child_field);
				}
				$categ_data[$parent_field][$child_field] = $categ;
			}
			else {
				$categ_data[$category->parent_id][] = $categ;
			}
		}
		return $categ_data;
	}

	public function delete() {
		// remove all link records
		if ($this->link_type == 'link-table') {
			$sql = "DELETE FROM ".$this->table."_link WHERE ".$this->primary_key."=".$this->id;
			$this->database->executeQuery($sql);
		}
		elseif ($this->link_type == 'foreign-key') {
			$sql = "UPDATE ".$this->link_from." SET ".$this->table."_id=NULL WHERE ".$this->primary_key."=".$this->id;
			$this->database->executeQuery($sql);
		}

		parent::delete();
	}

	public function getAsOptions($site_id = NULL, $params = []) {
		if (!$site_id) $site_id = $this->config->siteConfig()->site_id;
		if (is_array($site_id)) $site_id = ['type'=>'in', 'value'=>$site_id];
		$params['site_id'] = $site_id;
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

	public function getAsMenuArray($controller, $method, $method_params = [], $include_children = TRUE, $params = [], $li_class = '', $site_id = NULL) {
		if (!$site_id) $site_id = $this->config->siteConfig()->site_id;
		if (is_array($site_id)) $site_id = ['type'=>'in', 'value'=>$site_id];
		$params['site_id'] = $site_id;
		$categories = $this->getMulti($params, ['name' => 'asc']);
		$by_parent = [];
		$by_id = [];
		foreach ($categories as $category) {
			$by_parent[$category->parent_id][] = [
				'controller' => $controller,
				'method'     => $method,
				'params'     => array_merge($method_params, [$category->id, $category->getCanonicalName()]),
				'text'       => $category->name,
				'class'      => $li_class,
			];
			$by_id[$category->id] = &$by_parent[$category->parent_id][count($by_parent[$category->parent_id])-1];
		}

		foreach ($by_parent as $parent_id => &$categ) {
			if ($include_children && $parent_id != '') {
				$by_id[$parent_id]['children'] = $categ;
			}
		}

		if (isset($by_parent[NULL])) {
			return $by_parent[NULL];
		}
		return [];
	}

	public function getAsMenu($template, $language, $controller, $method, $method_params = [], $include_children, $params = [], $li_class = '', $site_id = NULL) {
		$options = $this->getAsMenuArray($controller, $method, $method_params, $include_children, $params, $li_class, $site_id);
		$menu = new Menu($this->config, $language);
		$menu->setTemplate($template);
		$menu->setMenuData($options);
		return $menu;
	}

	public function getCanonicalName() {
		return str_replace(' ', '-', strtolower($this->name));
	}

	public function hasImage() {
		return FALSE;
	}

	public function getImageUrl() {
		return NULL;
	}

	public function getImageThumbnailUrl() {
		return NULL;
	}

	
}
