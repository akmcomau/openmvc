<?php

namespace core\classes\renderable;

use core\classes\Authentication;
use core\classes\Config;
use core\classes\Database;
use core\classes\Logger;
use core\classes\Response;
use core\classes\Request;
use core\classes\URL;
use core\classes\Template;
use core\classes\Language;
use core\classes\Renderable;
use core\classes\renderable\Layout;

class Controller extends Renderable {

	protected $show_admin_layout = FALSE;

	protected $request;
	protected $response;
	protected $layout;
	protected $url;
	protected $authentication;
	protected $language;

	protected $permissions = [];

	public function __construct(Config $config, Database $database, Request $request, Response $response) {
		parent::__construct($config, $database);
		$this->request        = $request;
		$this->response       = $response;
		$this->url            = new URL($config);
		$this->authentication = new Authentication($config, $database, $request);
		$this->language       = new Language($config);

		if ($this->show_admin_layout) {
			$layout_class    = $config->siteConfig()->admin_layout_class;
			$layout_template = $config->siteConfig()->admin_layout_template;
		}
		else {
			$layout_class    = $config->siteConfig()->default_layout_class;
			$layout_template = $config->siteConfig()->default_layout_template;
		}

		$this->layout    = new $layout_class($config, $database, $request, $response, $this->authentication, $this->language, $layout_template);
	}

	public function getRequest() {
		return $this->request;
	}

	public function getAuthentication() {
		return $this->authentication;
	}

	public function getPermissions() {
		return $this->permissions;
	}

	public function getLayout() {
		return $this->layout;
	}

	public function getTemplate($filename, array $data = NULL) {
		return new Template($this->config, $this->language, $filename, $data);
	}

	public function setLayout(Layout $layout = NULL) {
		$this->layout = $layout;
	}

	public function render() {
		// Nothing needed here
	}
}
