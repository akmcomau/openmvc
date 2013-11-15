<?php

namespace core\controllers;

use core\classes\exceptions\SoftRedirectException;
use core\classes\exceptions\RedirectException;
use core\classes\exceptions\TemplateException;
use core\classes\renderable\Controller;
use core\classes\Email;
use core\classes\URL;
use core\classes\FormValidator;

class Root extends Controller {

	public function index() {
		$template = $this->getTemplate('pages/homepage.php');
		$this->response->setContent($template->render());
	}

	public function page($page_name = NULL) {
		// go to homepage if there is no page
		if (!$page_name) {
			throw new SoftRedirectException(__CLASS__, 'error_404');
		}

		$page_data = $this->url->getMethodConfig('Root', "page/$page_name");
		if (!$page_data) {
			$this->logger->debug('Method config not found');
			throw new SoftRedirectException(__CLASS__, 'error_404');
		}

		if (isset($page_data['language'])) {
			foreach ($page_data['language'] as $file) {
				$this->language->loadLanguageFile($file);
			}
		}

		$data = [];
		if (isset($page_data['data'])) {
			foreach ($page_data['data'] as $property => $method) {
				$data[$property] = $this->$method();
			}
		}

		try {
			$template = $this->getTemplate("pages/misc/$page_name.php", $data);
			$this->response->setContent('<div class="container">'.$template->render().'</div>');
		}
		catch (TemplateException $ex) {
			$this->logger->debug('Misc page template not found');
			throw new SoftRedirectException(__CLASS__, 'error_404');
		}
	}

	public function contactUsSend() {
		$this->language->loadLanguageFile('contact_us.php');
		$site_params = $this->config->siteConfig();
		$form = $this->contactUsForm();

		if ($form->validate()) {
			$data = [];
			foreach ($site_params->contact_fields as $property => $stuff) {
				$data['fields'][$this->language->get($property)] = $this->request->postParam($property);
			}

			$body = $this->getTemplate('emails/enquiry.txt.php', $data);
			$html = $this->getTemplate('emails/enquiry.html.php', $data);
			$email = new Email($this->config);
			$email->setFromEmail($this->request->postParam('email'));
			$email->setToEmail($this->config->siteConfig()->email_addresses->contact_us);
			$email->setSubject($this->config->siteConfig()->name.': Website Enquiry');
			$email->setBodyTemplate($body);
			$email->setHtmlTemplate($html);

			if ($email->send()) {
				throw new RedirectException($this->url->getUrl('Root', 'page/contact_us_sent'));
			}
			else {
				throw new RedirectException($this->url->getUrl('Error', 'error_500'));
			}
		}

		throw new SoftRedirectException(__CLASS__, 'page/contact_us');
	}

	protected function contactUsForm() {
		$inputs = [];
		$site_params = $this->config->siteConfig();
		foreach ($site_params->contact_fields as $property => $data) {
			if (property_exists($data, 'message_text_tag')) {
				$data->message = $this->language->get($data->message_text_tag);
			}
			$inputs[$property] = (array)$data;
		}

		return new FormValidator($this->request, 'form-contact-us', $inputs);
	}

	public function error_401() {
		$this->language->loadLanguageFile('error.php');
		header("HTTP/1.1 401 Permission Denied");
		$template = $this->getTemplate('pages/error_401.php');
		$this->response->setContent($template->render());
	}

	public function error_404() {
		$this->language->loadLanguageFile('error.php');
		header("HTTP/1.1 404 Not Found");
		$template = $this->getTemplate('pages/error_404.php');
		$this->response->setContent($template->render());
	}

	public function error_500() {
		$this->language->loadLanguageFile('error.php');
		header("HTTP/1.1 500 Internal Server Error");
		$template = $this->getTemplate('pages/error_500.php');
		$this->response->setContent($template->render());
	}

	public function getAllMethods() {
		$methods = parent::getAllMethods();
		$url_map = $this->url->getUrlMap();
		$controller_map = $url_map['forward']['Root'];
		if (isset($controller_map['methods'])) {
			foreach ($controller_map['methods'] as $method => $data) {
				if (!in_array($method, $methods)) {
					$methods[] = $method;
				}
			}
		}
		return $methods;
	}
}