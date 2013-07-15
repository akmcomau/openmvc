<?php

namespace core\classes;

use core\classes\exceptions\TemplateException;
use core\classes\Controller;
use core\classes\Database;

class Template {

	private $logger;
	private $controller;
	private $filename = NULL;
	private $data = NULL;

	public function __construct(Controller $controller, $filename) {
		$this->controller = $controller;
		$this->filename = $filename;
		$this->logger = Logger::getLogger(__CLASS__);
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

		if ($this->data) {
			extract($this->data);
		}

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

		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$template_path = 'core'.DS.'themes'.DS.'default'.DS.'templates'.DS;
		$template_file = $root_path.$template_path.$this->filename;
		if (!file_exists($template_file)) {
			throw new TemplateException("Could not find template file: {$this->filename}");
		}

		return $template_file;
	}

	public function getURL($controller_name = NULL, $method_name = NULL, $params = NULL) {
		return $this->controller->getURL($controller, $method, $params);
	}

	public function getSecureURL($controller_name = NULL, $method_name = NULL, $params = NULL) {
		return $this->controller->getSecureURL($controller_name, $method_name, $params);
	}
}
