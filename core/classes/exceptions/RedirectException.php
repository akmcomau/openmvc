<?php

namespace core\classes\exceptions;

use Exception;

class RedirectException extends Exception {
	private $url;

	public function __construct($url) {
		$this->url = $url;
		parent::__construct("Redirect to: $url");
	}

	public function getURL() {
		return $this->url;
	}
}
