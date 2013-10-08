<?php

namespace core\classes;

use core\classes\exceptions\ModelException;
use core\classes\URL;

class Page {

	protected $config;

	public function __construct(Config $config, Database $database) {
		$this->config   = $config;
		$this->database = $database;
		$this->url      = new URL($this->config);
		$this->logger   = Logger::getLogger(__CLASS__);
	}

	public function getPageList() {
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

				$meta_tags = $this->url->getMethodMetaTags($controller_name, $method, FALSE);
				$main_method = $method;
				$sub_method  = NULL;
				if (preg_match('/^page\/(.*)$/', $method, $matches)) {
					$main_method = 'page';
					$sub_method  = $matches[1];
				}

				$method = [
					'url' => $this->url->getURL($controller_name, $method),
					'title' => $meta_tags['title'],
					'description' => !empty($meta_tags['description']),
					'keywords' => !empty($meta_tags['keywords']),
					'permissions' => isset($permissions[$method]) ? join(', ', $permissions[$method]) : '',
					'controller' => $controller_name,
					'method' => $method,
					'main_method' => $main_method,
					'sub_method' => $sub_method,
					'editable' => $controller_name == 'Root' && preg_match('/^page\//', $method),
				];
				$pages[] = $method;
			}
		}

		return $pages;
	}

	public function getPage($controller = NULL, $method = NULL) {
		if (is_null($controller) || is_null($method)) {
			return [
				'url'              => $this->url->getURL('Root', 'page', ['--NOT_SET--']),
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

		$template = NULL;

		return [
			'url'              => $this->url->getURL($controller, $method),
			'meta_tags'        => $meta_tags,
			'link_text'        => $this->url->getLinkText($controller, $method),
			'controller'       => $controller,
			'method'           => preg_replace('/^page\//', '', $method),
			'misc_page'        => preg_match('/^page\/.+/', $method),
			'controller_alias' => $this->url->seoController($controller),
			'method_alias'     => $this->url->seoMethod($controller, $method),
			'content'          => '',
			'category'         => $this->url->getCategory($controller, $method),
		];
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
		$controller_map = $url_map['forward'][$controller];
		if (!$overwrite && isset($controller_map['methods'][$method])) {
			return FALSE;
		}

		$method_map = [
			'aliases'   => [$language => $data['method_alias']],
			'meta_tags' => []
		];
		if (!empty($data['link_text'])) {
			$method_map['link_text'] = [$language => $data['link_text']];
		}
		if (!empty($data['link_text'])) {
			$method_map['link_text'] = [$language => $data['link_text']];
		}
		if (!empty($data['meta_tags']['title'])) {
			$method_map['meta_tags']['title'] = [$language => $data['meta_tags']['title']];
		}
		if (!empty($data['meta_tags']['description'])) {
			$method_map['meta_tags']['description'] = [$language => $data['meta_tags']['description']];
		}
		if (!empty($data['meta_tags']['keywords'])) {
			$method_map['meta_tags']['keywords'] = [$language => $data['meta_tags']['keywords']];
		}
		if (!empty($data['category'])) {
			$method_map['category'] = $data['category'];
		}

		$controller_map['aliases'][$language] = $data['controller_alias'];
		$controller_map['methods'][$method] = $method_map;

		// Update meta
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$site_path = $root_path.'sites'.DS.$site->namespace.DS.'meta'.DS.$language.DS;
		$site_file = $site_path.$controller.'.php';

		if (!is_dir($site_path)) {
			mkdir($site_path, 0775, TRUE);
		}
		file_put_contents($site_file, '<?php $_URLS = '.var_export($controller_map, TRUE).';');

		// Update the template file
		if ($data['misc_page']) {
			$theme_path = $root_path.'sites'.DS.$site->namespace.DS.'themes'.DS.$theme.DS.'templates'.DS.'pages'.DS.'misc'.DS;
			$theme_file = $theme_path.$data['method'].'.php';

			if (!is_dir($theme_path)) {
				mkdir($theme_path, 0775, TRUE);
			}
			file_put_contents($theme_file, $data['content']);
		}
	}
}