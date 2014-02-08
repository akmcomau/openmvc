<?php

namespace core\classes;

use core\classes\exceptions\TemplateException;
use core\classes\Config;
use core\classes\Database;
use core\classes\Language;
use core\classes\URL;

class Template {
	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The logger object
	 * @var Logger $logger
	 */
	protected $logger;

	protected $filename = NULL;
	protected $data = NULL;
	protected $url;
	protected $path;
	protected $language;

	public function __construct(Config $config, Language $language, $filename, $data = NULL, $path = NULL) {
		$this->config = $config;
		$this->language = $language;
		$this->filename = $filename;
		$this->data = $data;
		$this->path = $path;
		$this->logger = Logger::getLogger(__CLASS__);
		$this->url    = new URL($this->config);
	}

	public function getFilename() {
		return $this->filename;
	}

	public function setFilename($filename) {
		$this->filename = $filename;
	}

	public function getData() {
		return $this->data;
	}

	public function setData($data) {
		$this->data = $data;
	}

	public function getTemplateContent() {
		return file_get_contents($this->getAbsoluteFilename());
	}

	public function render() {
		$filename = $this->getAbsoluteFilename();

		// default data for the template
		$this->data['page_div_class'] = $this->config->siteConfig()->page_div_class;
		$this->data['site_url'] = $this->config->getSiteUrl();
		$this->data['static_prefix'] = '/'.$this->config->siteConfig()->static_prefix;
		extract($this->data);

		// load the language
		$strings = $this->language->getStrings();
		extract($strings, EXTR_PREFIX_ALL, 'text');

		ob_start();
		require($filename);
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	public function getAbsoluteFilename() {
		if (!$this->filename) {
			throw new TemplateException('No template filename set');
		}

		$site = $this->config->siteConfig();
		$theme = $site->theme;

		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$default_path = 'core'.DS.'themes'.DS.'default'.DS.'templates'.DS;
		$default_file = $root_path.$default_path.$this->filename;
		$theme_path = 'sites'.DS.$site->namespace.DS.'themes'.DS.$theme.DS.'templates'.DS;
		$theme_file = $root_path.$theme_path.$this->filename;
		if ($this->path) {
			$path_path = $this->path.DS.'templates'.DS;
			$path_file = $root_path.$path_path.$this->filename;

			$theme_path_file = $root_path.$theme_path.$this->path.DS.$this->filename;
		}
		if (file_exists($theme_file)) {
			return $theme_file;
		}
		if (file_exists($default_file)) {
			return $default_file;
		}
		if ($this->path && file_exists($theme_path_file)) {
			return $theme_path_file;
		}
		if ($this->path && file_exists($path_file)) {
			return $path_file;
		}

		throw new TemplateException("Could not find template file: {$this->filename}");
	}
}
