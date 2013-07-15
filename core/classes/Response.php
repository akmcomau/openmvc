<?php

namespace core\classes;

class Response {
	private $headers = [];
	private $content = '';

	public function getContent() {
		return $this->content;
	}

	public function setContent($content) {
		$this->content = $content;
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
