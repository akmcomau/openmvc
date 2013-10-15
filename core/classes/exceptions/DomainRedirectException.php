<?php

namespace core\classes\exceptions;

use Exception;

class DomainRedirectException extends Exception {
	protected $domain;

	public function __construct($domain) {
		$this->domain = $domain;
		parent::__construct("Redirect correct domain: $domain");
	}

	public function getDomain() {
		return $this->domain;
	}
}
