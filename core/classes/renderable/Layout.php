<?php

namespace core\classes\renderable;

use core\classes\Renderable;
use core\classes\Config;
use core\classes\Database;
use core\classes\Response;
use core\classes\Request;
use core\classes\Template;
use core\classes\URL;

class Layout extends Renderable {

	protected $request;
	protected $response;
	protected $template;
	protected $url;

	public function __construct(Config $config, Database $database, Request $request, Response $response, $template) {
		parent::__construct($config, $database);
		$this->request  = $request;
		$this->response = $response;
		$this->template = $template;
		$this->url      = new URL($config);
	}

	public function render() {
		$template = new Template($this->config, $this->template);
		$template->setData([
			'page_content' => $this->response->getContent(),
		]);
		$this->response->setContent($template->render());
	}

	public function getRequest() {
		return $this->request;
	}
}
