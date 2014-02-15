<?php

namespace core\classes;

class Response {
	protected $headers = [];
	protected $content = '';

	public function getContent() {
		return $this->content;
	}

	public function setContent($content) {
		$this->content = $content;
	}

	public function setJsonContent($controller, $content) {
		$controller->setLayout(NULL);
		$this->addHeader('Content-Type', 'application/json');
		$this->content = $content;
	}

	public function setCsvContent($controller, $content) {
		$controller->setLayout(NULL);
		$this->addHeader('Content-Type', 'text/csv');
		$this->content = $content;
	}

	public function setXmlContent($controller, $content) {
		$controller->setLayout(NULL);
		$this->addHeader('Content-Type', 'text/xml');
		$this->content = $content;
	}

	public function arrayToCsv(array &$array) {
		if (count($array) == 0) {
			return null;
		}
		ob_start();
		$df = fopen("php://output", 'w');
		foreach ($array as $row) {
			fputcsv($df, $row);
		}
		fclose($df);
		return ob_get_clean();
	}

	public function hashToCsv(array &$array) {
		if (count($array) == 0) {
			return null;
		}
		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array_keys(reset($array)));
		foreach ($array as $row) {
			fputcsv($df, $row);
		}
		fclose($df);
		return ob_get_clean();
	}

	public function appendContent($content) {
		$this->content .= $content;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getHeader($type) {
		if (isset($this->headers[$type])) {
			return $this->headers[$type];
		}
		else {
			return NULL;
		}
	}

	public function addHeader($type, $value) {
		$this->headers[$type] = $value;
	}

	public function sendContent() {
		print $this->content;
	}

	public function sendHeaders() {
		foreach ($this->headers as $type => $value) {
			header("$type: $value");
		}
	}
}
