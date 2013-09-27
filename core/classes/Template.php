<?php

namespace core\classes;

use core\classes\exceptions\TemplateException;
use core\classes\Config;
use core\classes\Database;
use core\classes\Language;
use core\classes\URL;

class Template {

	protected $logger;
	protected $config;
	protected $filename = NULL;
	protected $data = NULL;
	protected $url;
	protected $language;

	public function __construct(Config $config, Language $language, $filename, $data = NULL) {
		$this->config = $config;
		$this->language = $language;
		$this->filename = $filename;
		$this->data = $data;
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

	public function render() {
		$filename = $this->getAbsoluteFilename();

		// default data for the template
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
		if (file_exists($theme_file)) {
			return $theme_file;
		}
		if (file_exists($default_file)) {
			return $default_file;
		}
		else {
			throw new TemplateException("Could not find template file: {$this->filename}");
		}
	}

	public function getURL($controller_name = NULL, $method_name = NULL, array $params = NULL) {
		return $this->url->getURL($controller_name, $method_name, $params);
	}

	public function getSecureURL($controller_name = NULL, $method_name = NULL, array $params = NULL) {
		return $this->url->getSecureURL($controller_name, $method_name, $params);
	}

	public function getInformationURL($page) {
		return $this->url->getInformationURL($page);
	}
}
