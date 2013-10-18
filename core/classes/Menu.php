<?php

namespace core\classes;

class Menu {
	protected $url;
	protected $template;
	protected $template_dropdown;
	protected $ul_class;
	protected $a_class;
	protected $menu_data = [];

	public function __construct(Config $config, Language $language, $template, $ul_class = '', $a_class = '') {
		$this->url = new URL($config);
		$this->template = new Template($config, $language, $template);
		$this->template_dropdown = new Template($config, $language, substr($template, 0, -4).'_dropdown.php');
		$this->ul_class = $ul_class;
		$this->a_class = $a_class;
	}

	public function setData(array $data) {
		$this->menu_data = $data;
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

			$class = $children ? 'dropdown' : '';
			$class .= isset($item['class']) ? ' '.$item['class'] : '';
			print '<li class="'.$class.'">';

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
