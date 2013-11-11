<?php

namespace core\classes\exceptions;

use Exception;

class RedirectException extends Exception {
	protected $url;

	public function __construct($url) {
		$this->url = $url;
		parent::__construct("Redirect to: $url");
	}

	public function getUrl() {
		return $this->url;
	}
}
