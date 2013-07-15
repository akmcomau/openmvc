<?php

namespace core\classes;

class Layout extends Controller {
	protected $template;

	public function __construct(Config $config, Database $database, Request $request, Response $response, $template) {
		$this->config   = $config;
		$this->datbase  = $database;
		$this->request  = $request;
		$this->response = $response;
		$this->template = $template;
		$this->logger   = Logger::getLogger(__CLASS__);
	}

	public function render() {
		$template = new Template($this, $this->template);
		$template->setData([
			'page_content' => $this->response->getContent(),
		]);
		$this->response->setContent($template->render());
	}
}
