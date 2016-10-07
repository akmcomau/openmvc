<?php

namespace core\classes;

use core\classes\exceptions\ModelException;

class Page {

	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The database object
	 * @var Database $database
	 */
	protected $database;

	public function __construct(Config $config, Database $database) {
		$this->config   = $config;
		$this->database = $database;
		$this->url      = new URL($this->config);
		$this->logger   = Logger::getLogger(__CLASS__);
	}

	public function getPageList(array $params = NULL, array $ordering = NULL, array $pagination = NULL) {
		$model = new Model($this->config, $this->database);
		$categories = $model->getModel('\core\classes\models\PageCategoryLink');
		$page_categories = $categories->getPageCategories();

		$pages = [];
		$controllers = $this->url->listAllControllers();
		foreach ($controllers as $controller_name => $controller_class) {
			// Ignore admin pages
			if (preg_match('/\\\\(a|A)dministrator\\\\?/', $controller_class)) continue;

			$controller = new $controller_class($this->config);
			$controller->setUrl($this->url);
			$methods = $controller->getAllMethods();
			$permissions = $controller->getPermissions();
			foreach ($methods as $method) {
				// skip the Root::page method
				if ($method == 'page' && $controller_name == 'Root') continue;
				if ($controller_name == 'Root' && preg_match('/^error_/', $method)) continue;

				$category = NULL;
				if (isset($page_categories[$controller_name][$method])) {
					$category = $page_categories[$controller_name][$method];
				}
				$meta_tags = $this->url->getMethodMetaTags($controller_name, $method, FALSE);
				$main_method = $method;
				$sub_method  = NULL;
				if (preg_match('/^page\/(.*)$/', $method, $matches)) {
					$main_method = 'page';
					$sub_method  = $matches[1];
				}

				$method = [
					'url' => $this->url->getUrl($controller_name, $method),
					'title' => $meta_tags['title'],
					'description' => !empty($meta_tags['description']),
					'keywords' => !empty($meta_tags['keywords']),
					'permissions' => isset($permissions[$method]) ? join(', ', $permissions[$method]) : '',
					'controller' => $controller_name,
					'method' => $method,
					'main_method' => $main_method,
					'sub_method' => $sub_method,
					'category_id' => $category ? $category->id : NULL,
					'category_name' => $category ? $category->name : NULL,
					'editable' => $controller_name == 'Root' && preg_match('/^page\//', $method),
				];

				// check the params
				$add_method = TRUE;
				if ($params) {
					foreach ($params as $property => $value) {
						if ($property == 'category') $property = 'category_id';

						$value = strtolower($value);
						if ($property == 'editable') {
							if ($value == 'editable' && !$method[$property]) {
								$add_method = FALSE;
							}
							elseif ($value == 'fixed' && $method[$property]) {
								$add_method = FALSE;
							}
						}
						elseif (strpos(strtolower($method[$property]), $value) === FALSE) {
							$add_method = FALSE;
						}
					}
				}

				if ($add_method) {
					$pages[] = $method;
				}
			}
		}

		// do the ordering
		if ($ordering) {
			foreach ($ordering as $field => $direction) {
				$direction = (strtolower($direction) == 'asc') ? TRUE : FALSE;

				// sort the list
				if (in_array($field, ['url', 'title', 'keywords', 'description', 'permissions'])) {
					usort($pages, function ($a, $b) use ($field, $direction) {
						if ($a[$field] < $b[$field]) return $direction ? -1 : 1;
						if ($a[$field] > $b[$field]) return $direction ? 1 : -1;
						return 0;
					});
				}
			}
		}

		// do the pagination
		if ($pagination) {
			$pages = array_splice($pages, $pagination['offset'], $pagination['limit']);
		}

		return $pages;
	}

	public function getPage($controller = NULL, $method = NULL) {
		if (is_null($controller) || is_null($method)) {
			return [
				'url'              => $this->url->getUrl('Root', 'page', ['--NOT_SET--']),
				'meta_tags'        => ['title'=>'', 'description'=>'', 'keywords'=>''],
				'link_text'        => '',
				'controller'       => 'Root',
				'method'           => '',
				'misc_page'        => TRUE,
				'controller_alias' => 'Root',
				'method_alias'     => '',
				'content'          => '',
				'category'         => '',
			];
		}

		$meta_tags = $this->url->getMethodMetaTags($controller, $method, FALSE);
		if (!isset($meta_tags['title'])) $meta_tags['title'] = NULL;
		if (!isset($meta_tags['description'])) $meta_tags['description'] = NULL;
		if (!isset($meta_tags['keywords'])) $meta_tags['keywords'] = NULL;

		$category_id = '';
		$model = new Model($this->config, $this->database);
		$page  = $model->getModel('\\core\\classes\\models\\PageCategoryLink')->get([
			'page_controller' => $controller,
			'page_method' => $method,
		]);
		if ($page) {
			$category_id = $page->page_category_id;
		}

		return [
			'url'              => $this->url->getUrl($controller, $method),
			'meta_tags'        => $meta_tags,
			'link_text'        => $this->url->getLinkText($controller, $method),
			'controller'       => $controller,
			'method'           => preg_replace('/^page\//', '', $method),
			'misc_page'        => preg_match('/^page\/.+/', $method),
			'controller_alias' => $this->url->seoController($controller),
			'method_alias'     => $this->url->seoMethod($controller, $method),
			'content'          => '',
			'category'         => $category_id,
		];
	}

	public function delete($controller, $method, $sub_method) {
		$site       = $this->config->siteConfig();
		$language   = $site->language;
		$theme      = $site->theme;
		$url_map    = $this->url->getUrlMap();

		if (!isset($url_map['forward'][$controller])) {
			throw new \ErrorException('Controller map does not exist');
		}

		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$core_path = $root_path.'core'.DS.'meta'.DS;
		$core_file = $core_path.$controller.'.php';
		$site_path = $root_path.'sites'.DS.$site->namespace.DS.'meta'.DS;
		$site_file = $site_path.$controller.'.php';

		$core_controller_map = NULL;
		if (file_exists($core_file)) {
			require($core_file);
			$core_controller_map = $_URLS;
		}

		if (file_exists($site_file)) {
			require($site_file);
			if ($core_controller_map) {
				$controller_map = array_merge($core_controller_map, $_URLS);
			}
			else {
				$controller_map = $_URLS;
			}
		}
		else {
			if ($core_controller_map) {
				$controller_map = $core_controller_map;
			}
			else {
				$controller_map = ['methods'=>[]];
			}
		}

		if ($controller_map['methods'][$method.'/'.$sub_method]) {
			unset($controller_map['methods'][$method.'/'.$sub_method]);
		}

		// Update meta
		if (!is_dir($site_path)) {
			mkdir($site_path, 0775, TRUE);
		}
		file_put_contents($site_file, '<?php $_URLS = '.var_export($controller_map, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($site_file);
		}

		// remove the template file
		$theme_path = $root_path.'sites'.DS.$site->namespace.DS.'themes'.DS.$theme.DS.'templates'.DS.'pages'.DS.'misc'.DS;
		$theme_file = $theme_path.$sub_method.'.php';

		if (file_exists($theme_file)) {
			unlink($theme_file);
		}

		// delete all page_category_links
		$model = new Model($this->config, $this->database);
		$link = $model->getModel('\core\classes\models\PageCategoryLink');
		$link->deletePage($controller, $method.'/'.$sub_method);
	}

	public function update(array $data, $overwrite) {
		$site       = $this->config->siteConfig();
		$language   = $site->language;
		$theme      = $site->theme;
		$url_map    = $this->url->getUrlMap();
		$controller = $data['controller'];
		if ($data['misc_page']) {
			$method = 'page/'.$data['method'];
		}
		else {
			$method = $data['method'];
		}

		if (!isset($url_map['forward'][$controller])) {
			throw new \ErrorException('Controller map does not exist');
		}

		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$core_path = $root_path.'core'.DS.'meta'.DS;
		$core_file = $core_path.$controller.'.php';
		$site_path = $root_path.'sites'.DS.$site->namespace.DS.'meta'.DS;
		$site_file = $site_path.$controller.'.php';

		$core_controller_map = NULL;
		if (file_exists($core_file)) {
			require($core_file);
			$core_controller_map = $_URLS;
		}

		if (file_exists($site_file)) {
			require($site_file);
			if ($core_controller_map) {
				$controller_map = array_merge($core_controller_map, $_URLS);
			}
			else {
				$controller_map = $_URLS;
			}
		}
		else {
			if ($core_controller_map) {
				$controller_map = $core_controller_map;
			}
			else {
				$controller_map = ['methods'=>[]];
			}
		}

		if (!$overwrite && isset($controller_map['methods'][$method])) {
			return FALSE;
		}

		if (isset($controller_map['methods'][$method])) {
			$method_map = $controller_map['methods'][$method];
			$method_map['aliases'][$language] = $data['method_alias'];
		}
		else {
			$method_map = [
				'aliases'   => [$language => $data['method_alias']],
				'meta_tags' => []
			];
		}
		if (!empty($data['link_text'])) {
			$method_map['link_text'][$language] = $data['link_text'];
		}

		foreach ($data['meta_tags'] as $property => $value) {
			if (!empty($value)) {
				$method_map['meta_tags'][$property][$language] = $value;
			}
			elseif (isset($method_map['meta_tags'][$property][$language])) {
				unset($method_map['meta_tags'][$property][$language]);
			}
		}

		if (empty($data['category'])) {
			$model = new Model($this->config, $this->database);
			$page  = $model->getModel('\\core\\classes\\models\\PageCategoryLink')->get([
				'page_controller' => $controller,
				'page_method' => $method,
			]);
			if ($page) {
				$page->delete();
			}
		}
		else {
			$model = new Model($this->config, $this->database);
			$page  = $model->getModel('\\core\\classes\\models\\PageCategoryLink')->get([
				'page_controller' => $controller,
				'page_method' => $method,
			]);
			if ($page) {
				$page->page_category_id = (int)$data['category'];
			}
			else {
				$page  = $model->getModel('\\core\\classes\\models\\PageCategoryLink');
				$page->page_controller = $controller;
				$page->page_method = $method;
				$page->page_category_id = (int)$data['category'];
				$page->insert();
			}
		}

		$controller_map['aliases'][$language] = $data['controller_alias'];
		$controller_map['methods'][$method] = $method_map;

		// Update meta
		if (!is_dir($site_path)) {
			mkdir($site_path, 0775, TRUE);
		}
		file_put_contents($site_file, '<?php $_URLS = '.var_export($controller_map, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($site_file);
		}

		// Update the template file
		if ($data['misc_page']) {
			$theme_path = $root_path.'sites'.DS.$site->namespace.DS.'themes'.DS.$theme.DS.'templates'.DS.'pages'.DS.'misc'.DS;
			$theme_file = $theme_path.$data['method'].'.php';

			$data['content'] = preg_replace('/(\n\r?)+/', "\n", $data['content']);

			if (!is_dir($theme_path)) {
				mkdir($theme_path, 0775, TRUE);
			}
			file_put_contents($theme_file, $data['content']);
			if (function_exists('opcache_invalidate')) {
				opcache_invalidate($theme_file);
			}
		}
	}
}
