<?php

namespace core\classes\renderable;

use core\classes\Renderable;
use core\classes\Template;
use core\classes\Language;
use core\classes\Config;
use core\classes\Database;
use core\classes\Logger;
use core\classes\Request;
use core\classes\URL;

abstract class Widget extends Renderable {

	protected $request;
	protected $response;
	protected $url;
	protected $language;

	public function __construct(Config $config, Database $database, Request $request, Language $language) {

		// Controller has been created for meta data, don't need much
		if (is_null($request)) {
			$this->config = $config;
			return;
		}

		parent::__construct($config, $database);

		$this->request        = $request;
		$this->language       = $language;
		$this->url            = new URL($config);
	}

	public function getTemplate($filename, array $data = NULL, $path = NULL) {
		return new Template($this->config, $this->language, $filename, $data, $path);
	}

}
