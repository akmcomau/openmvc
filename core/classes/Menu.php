<?php

namespace core\classes;

use core\classes\exceptions\TemplateException;

class Menu {
	protected $config;
	protected $language;
	protected $authentication;
	protected $url;
	protected $template;
	protected $template_dropdown;
	protected $ul_class;
	protected $a_class;
	protected $menu_data = [];

	public function __construct(Config $config, Language $language, Authentication $authentication) {
		$this->config = $config;
		$this->language = $language;
		$this->authentication = $authentication;
		$this->url = new URL($config);
	}

	public function loadMenu($filename) {
		$site = $this->config->siteConfig();
		$core_file = __DIR__.DS.'..'.DS.'config'.DS.$filename;
		$site_file = __DIR__.DS.'..'.DS.'..'.DS.'sites'.DS.$site->namespace.DS.'config'.DS.$filename;
		if (file_exists($site_file)) {
			require($site_file);
		}
		elseif (file_exists($core_file)) {
			require($core_file);
		}

		if (isset($_MENU) && is_array($_MENU)) {
			$this->template = new Template($this->config, $this->language, $_MENU['template']);
			$this->template_dropdown = new Template($this->config, $this->language, substr($_MENU['template'], 0, -4).'_dropdown.php');
			$this->ul_class = $_MENU['ul_class'];
			$this->a_class = $_MENU['a_class'];
			$this->menu_data = $_MENU['menu'];
		}
		else {
			throw new TemplateException("Could not find template file: $filename");
		}
	}

	public function getMenuData() {
		return $this->menu_data;
	}

	public function addMenuData($name, $value) {
		if (is_array($name)) {
			$data = &$this->menu_data;
			foreach ($name as $element) {
				if (!isset($data[$element])) {
					$data[$element] = NULL;
				}
				$data = &$data[$element];
			}
			$data = $value;
			return $value;
		}

		$this->menu_data[$name] = $value;
		return $value;
	}

	protected function preprocessMenuData(&$item) {
		if (isset($item['controller']) && isset($item['method'])) {
			$params = [];
			if (isset($item['params'])) $params = $item['params'];
			$item['url'] = $this->url->getURL($item['controller'], $item['method'], $params);
			if (!isset($item['text'])) {
				$item['text'] = $this->url->getLinkText($item['controller'], $item['method']);
			}
		}
		unset($item['controller']);
		unset($item['method']);
		unset($item['params']);

		if (isset($item['text_tag'])) {
			$item['text'] = $this->language->get($item['text_tag']);
		}
		unset($item['text_tag']);
	}

	public function echoBootstrapMenu() {
		$template = $this->template->render();
		$template_dropdown = $this->template_dropdown->render();

		print '<ul class="'.$this->ul_class.'">';
		foreach ($this->menu_data as $item) {
			$children = NULL;
			if (isset($item['children']) && is_array($item['children'])) {
				$children = $item['children'];
			}
			unset($item['children']);

			$this->preprocessMenuData($item);

			$class = $children ? 'dropdown' : '';
			$class .= isset($item['class']) ? ' '.$item['class'] : '';
			print '<li class="'.$class.'">';

			if ($children) {
				$html = $template_dropdown;
			}
			else {$html = $template;
				$html = $template;
			}
			foreach ($item as $key => $value) {
				$html = preg_replace('/\[%\s*'.$key.'\s*%\]/', htmlspecialchars($value), $html);
			}

			$attr = $children ? ' class="dropdown-toggle" data-toggle="dropdown"' : '';
			print '<a href="'.$item['url'].'"'.$attr.'>'.$html.'</a>';

			if ($children) {
				$this->recursiveBootstrapMenu($children, 1);
			}

			print '</li>';
		}
		print '</ul>';
	}

	protected function recursiveBootstrapMenu(array $menu, $depth) {
		print '<ul class="dropdown-menu">';
		foreach ($menu as $key => $item) {
			$this->preprocessMenuData($item);

			$children = NULL;
			if (isset($item['children']) && is_array($item['children'])) {
				$children = $item['children'];
			}
			$class = $children ? 'dropdown-submenu' : '';
			print '<li class="'.$class.'">';
			print '<a href="'.$item['url'].'" class="'.$this->a_class.'">'.$item['text'].'</a>';

			if ($children) {
				$this->recursiveBootstrapMenu($children, $depth++);
			}

			print '</li>';
		}
		print '</ul>';
	}
}
