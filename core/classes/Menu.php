<?php

namespace core\classes;

use ErrorException;
use core\classes\exceptions\TemplateException;

class Menu {
	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The language object
	 * @var Language $language
	 */
	protected $language;

	/**
	 * The authentication object
	 * @var Authentication $authentication
	 */
	protected $authentication;

	/**
	 * The URL object
	 * @var URL $url
	 */
	protected $url;

	protected $template;
	protected $template_dropdown;
	protected $ul_class;
	protected $a_class;
	protected $menu_data = [];
	protected $filename = [];

	public function __construct(Config $config, Language $language, Authentication $authentication = NULL) {
		$this->config = $config;
		$this->language = $language;
		$this->authentication = $authentication;
		$this->url = new URL($config);
	}

	public function loadMenu($filename) {
		$this->filename = $filename;
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

	public function insert_menu($after, $key, $data) {
		$array = &$this->menu_data;
		if (is_array($after)) {
			$counter = 0;
			foreach ($after as $element) {
				$counter++;
				if ($counter == count($after)) {
					$after = $element;
					break;
				}
				if (!isset($array[$element]['children'])) {
					throw new ErrorException("Menu item does not exist ($element): ".print_r($after, TRUE));
				}
				$array = &$array[$element]['children'];
			}
		}

		$copy = $array;
		$array = [];

		if (is_null($after)) {
			$array[$key] = $data;
		}

		foreach ($copy as $menu_name => $menu_data) {
			$array[$menu_name] = $menu_data;
			if ($menu_name == $after) {
				$array[$key] = $data;
			}
		}
	}

	public function update() {
		$site = $this->config->siteConfig();
		$site_path = __DIR__.DS.'..'.DS.'..'.DS.'sites'.DS.$site->namespace.DS.'config'.DS;
		$site_file = $site_path.$this->filename;

		$menu = [
			'template' => $this->template->getFilename(),
			'ul_class' => $this->ul_class,
			'a_class' => $this->a_class,
			'menu' => $this->menu_data,
		];

		if (!is_dir($site_path)) {
			mkdir($site_path, 0775, TRUE);
		}
		file_put_contents($site_file, '<?php $_MENU = '.var_export($menu, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($site_file);
		}
	}

	public function setTemplate($template, $ul_class = '', $a_class = '') {
		$this->template = new Template($this->config, $this->language, $template);
		$this->template_dropdown = new Template($this->config, $this->language, substr($template, 0, -4).'_dropdown.php');
		$this->ul_class = $ul_class;
		$this->a_class = $a_class;
	}

	public function getMenuData() {
		return $this->menu_data;
	}

	public function setMenuData($menu_data) {
		$this->menu_data = $menu_data;
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
			$item['url'] = $this->url->getUrl($item['controller'], $item['method'], $params);
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
			$a_class = $this->a_class.(isset($item['class']) ? ' '.$item['class'] : '');
			print '<li class="'.$class.'">';
			print '<a href="'.$item['url'].'" class="'.$a_class.'">'.$item['text'].'</a>';

			if ($children) {
				$this->recursiveBootstrapMenu($children, $depth++);
			}

			print '</li>';
		}
		print '</ul>';
	}
}
