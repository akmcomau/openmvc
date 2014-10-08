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

	public function getAllUrls($include_filter = NULL, $exclude_filter = NULL) {
		return parent::getAllUrls(NULL, '/(page$|^error)/');
	}

	public function index() {
		$template = $this->getTemplate('pages/homepage.php');
		$this->response->setContent($template->render());
	}

	public function page($page_name = NULL, $extra_data = NULL) {
		// go to homepage if there is no page
		if (!$page_name) {
			throw new SoftRedirectException(__CLASS__, 'error404');
		}

		$page_data = $this->url->getMethodConfig('Root', "page/$page_name");
		if (!$page_data) {
			$this->logger->info('Method config not found');
			throw new SoftRedirectException(__CLASS__, 'error404');
		}

		if (isset($page_data['language'])) {
			foreach ($page_data['language'] as $file) {
				$this->language->loadLanguageFile($file);
			}
		}

		$data = [];
		if (is_array($extra_data)) $data = $extra_data;
		if (isset($page_data['data'])) {
			foreach ($page_data['data'] as $property => $method) {
				$data[$property] = $this->$method();
			}
		}

		try {
			$template = $this->getTemplate("pages/misc/$page_name.php", $data);
			if ($this->config->siteConfig()->page_div_class === FALSE) {
				$this->response->setContent($template->render());
			}
			else {
				$this->response->setContent('<div class="'.$this->config->siteConfig()->page_div_class.'">'.$template->render().'</div>');
			}
		}
		catch (TemplateException $ex) {
			$this->logger->info('Misc page template not found');
			throw new SoftRedirectException(__CLASS__, 'error404');
		}
	}

	public function contactUs() {
		$this->language->loadLanguageFile('contact_us.php');
		$data['form'] = $this->contactUsForm();
		$template = $this->getTemplate('pages/contact_us.php', $data);
		$this->response->setContent($template->render());
	}

	public function contactUsSent() {
		$this->language->loadLanguageFile('contact_us.php');
		$template = $this->getTemplate('pages/contact_us_sent.php');
		$this->response->setContent($template->render());
	}

	public function contactUsSend() {
		$this->language->loadLanguageFile('contact_us.php');
		$site_params = $this->config->siteConfig();
		$form = $this->contactUsForm();

		if ($form->validate()) {
			$this->logger->info('Contact Us submitted from: '.$this->request->postParam('email'));
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
				throw new RedirectException($this->url->getUrl('Root', 'contactUsSent'));
			}
			else {
				throw new SoftRedirectException($this->url->getControllerClass('Root'), 'error500');
			}
		}

		throw new SoftRedirectException(__CLASS__, 'contactUs');
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

	public function error401() {
		$this->logger->info('Return error 401');
		$this->language->loadLanguageFile('error.php');
		header("HTTP/1.1 401 Permission Denied");
		$template = $this->getTemplate('pages/error_401.php');
		$this->response->setContent($template->render());
	}

	public function error404() {
		$this->logger->info('Return error 404');
		$this->language->loadLanguageFile('error.php');
		header("HTTP/1.1 404 Not Found");
		$template = $this->getTemplate('pages/error_404.php');
		$this->response->setContent($template->render());
	}

	public function error500() {
		$this->logger->info('Return error 500');
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