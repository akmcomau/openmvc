<?php

namespace core\classes\renderable;

use core\classes\Config;
use core\classes\Database;
use core\classes\Logger;
use core\classes\Response;
use core\classes\Request;
use core\classes\URL;
use core\classes\Renderable;

class Controller extends Renderable {

	protected $request;
	protected $response;
	protected $layout;
	protected $url;

	public function __construct(Config $config, Database $database, Request $request, Response $response) {
		parent::__construct($config, $database);
		$this->request  = $request;
		$this->response = $response;
		$this->url      = new URL($config);

		$layout_class    = $config->getSiteParams()->layout_class;
		$layout_template = $config->getSiteParams()->layout_template;
		$this->layout    = new $layout_class($config, $database, $request, $response, $layout_template);
	}

	public function getRequest() {
		return $this->request;
	}

	public function getLayout() {
		return $this->layout;
	}

	public function render() {
		// Nothing needed here
	}
}
