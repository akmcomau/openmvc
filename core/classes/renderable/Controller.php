<?php

namespace core\classes\renderable;

use core\classes\Authentication;
use core\classes\Config;
use core\classes\Database;
use core\classes\Logger;
use core\classes\Response;
use core\classes\Request;
use core\classes\URL;
use core\classes\Renderable;
use core\classes\renderable\Layout;

class Controller extends Renderable {

	protected $request;
	protected $response;
	protected $layout;
	protected $url;
	protected $authentication;

	public function __construct(Config $config, Database $database, Request $request, Response $response) {
		parent::__construct($config, $database);
		$this->request        = $request;
		$this->response       = $response;
		$this->url            = new URL($config);
		$this->authentication = new Authentication($config, $database, $request);

		$layout_class    = $config->getSiteParams()->layout_class;
		$layout_template = $config->getSiteParams()->layout_template;
		$this->layout    = new $layout_class($config, $database, $request, $response, $this->authentication, $layout_template);
	}

	public function getRequest() {
		return $this->request;
	}

	public function getLayout() {
		return $this->layout;
	}

	public function setLayout(Layout $layout = NULL) {
		$this->layout = $layout;
	}

	public function render() {
		// Nothing needed here
	}
}
