<?php

namespace core\classes;

use core\classes\exceptions\TemplateException;
use core\classes\Config;
use core\classes\Database;
use core\classes\URL;

class Template {

	private $logger;
	private $config;
	private $filename = NULL;
	private $data = NULL;
	protected $url;

	public function __construct(Config $config, $filename, $data = NULL) {
		$this->config = $config;
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
		$this->data['static_prefix'] = '/'.$this->config->getSiteParams()->static_prefix;
		extract($this->data);

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

		$site = $this->config->getSiteParams();
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
		return $this->controller->getURL($controller_name, $method_name, $params);
	}

	public function getSecureURL($controller_name = NULL, $method_name = NULL, array $params = NULL) {
		return $this->controller->getSecureURL($controller_name, $method_name, $params);
	}

	public function currentURL(array $params = NULL) {
		return $this->controller->currentURL($params);
	}

	public function getInformationURL($page) {
		return $this->controller->getInformationURL($page);
	}
}
