<?php

namespace core\classes\renderable;

use core\classes\Authentication;
use core\classes\Renderable;
use core\classes\Config;
use core\classes\Database;
use core\classes\Response;
use core\classes\Request;
use core\classes\Language;
use core\classes\Template;
use core\classes\URL;
use core\classes\Menu;

class Layout extends Renderable {

	protected $request;
	protected $response;
	protected $template;
	protected $url;
	protected $authentication;
	protected $language;
	protected $meta_tags = [];
	protected $controller;
	protected $method;
	protected $sub_page;
	protected $template_data = [];

	public function __construct(Config $config, Database $database, Request $request, Response $response, Authentication $auth, Language $language, $template) {
		parent::__construct($config, $database);
		$this->request  = $request;
		$this->response = $response;
		$this->language = $language;
		$this->template = $template;
		$this->url      = new URL($config);
		$this->authentication = $auth;
	}

	public function setTemplateData(array $data) {
		$this->template_data = $data;
	}

	public function setControllerMethod($controller, $method, $sub_page = NULL) {
		$this->controller = $controller;
		$this->method = $method;
		$this->sub_page = $sub_page;
	}

	public function loadLanguageFile($filename, $path = NULL) {
		$this->language->loadLanguageFile($filename, $path);
	}

	public function render() {
		$controller = '';
		$method = [ urlencode($this->controller), urlencode($this->method) ];
		if ($this->sub_page) {
			$method[] = $this->sub_page;
		}

		$data = [
			'method'                  => $method,
			'meta_tags'               => $this->meta_tags,
			'page_content'            => $this->response->getContent(),
			'logged_in'               => $this->authentication->loggedIn(),
			'customer_logged_in'      => $this->authentication->customerLoggedIn(),
			'administrator_logged_in' => $this->authentication->administratorLoggedIn(),
			'database'                => $this->database,
			'request'                 => $this->request,
		];
		$data = array_merge($data, $this->template_data);
		$template = new Template($this->config, $this->language, $this->template, $data);
		$this->response->setContent($template->render());
	}

	public function getRequest() {
		return $this->request;
	}

	public function addMetaTags($meta_tags, $overwrite = FALSE) {
		foreach ($meta_tags as $property => $value) {
			if (!isset($this->meta_tags[$property]) || $overwrite) {
				$this->meta_tags[$property] = $value;
			}
		}
	}
}
