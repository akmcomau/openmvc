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
		if ($this->config->siteConfig()->editable_homepage) {
			$template = $this->getTemplate('pages/misc/homepage.php');
		}
		else {
			$template = $this->getTemplate('pages/homepage.php');
		}
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

		$data = ['controller' => $this, 'model' => $this->model];
		if (is_array($extra_data)) $data = arary_merge($data, $extra_data);
		if (isset($page_data['data'])) {
			foreach ($page_data['data'] as $property => $method) {
				$data[$property] = $this->$method();
			}
		}

		try {
			$template = $this->getTemplate("pages/misc/$page_name.php", $data);
			if (isset($page_data['parent_template'])) {
				$template->setParentTemplate($page_data['parent_template']);
			}
			$this->response->setContent($template->render());
		}
		catch (TemplateException $ex) {
			$this->logger->info('Misc page template not found');
			throw new SoftRedirectException(__CLASS__, 'error404');
		}
	}

	public function contactUs($errors = '[]') {
		try {
			$errors = json_decode($errors, TRUE);
		}
		catch (Exception $ex) {
			$errors = [];
		}
		$this->language->loadLanguageFile('contact_us.php');
		$data['form'] = $this->contactUsForm();
		$data['form']->setErrors($errors);
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
			// check the recaptcha
			$recaptcha_success = TRUE;
			if ($this->config->siteConfig()->contact_enable_recaptcha) {
				$recaptcha_success = FALSE;
				if (isset($_REQUEST['g-recaptcha-response']) && !empty($_REQUEST['g-recaptcha-response'])) {
					$url = 'https://www.google.com/recaptcha/api/siteverify';

					$post_data = [
						'secret'   => $this->config->siteConfig()->contact_recaptcha_secret,
						'response' => $_REQUEST['g-recaptcha-response'],
						'remoteip' => $_SERVER['REMOTE_ADDR'],
					];

					$options = array(
						'http' => array(
							'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
							'method'  => 'POST',
							'content' => http_build_query($post_data)
						)
					);

					$context  = stream_context_create($options);
					$result = file_get_contents($url, false, $context);
					if ($result !== FALSE) {
						$result = json_decode($result);
						if ($result && is_object($result) && property_exists($result, 'success') && $result->success) {
							$recaptcha_success = TRUE;
						}
					}
				}
			}

			if (!$recaptcha_success) {
				throw new SoftRedirectException(__CLASS__, 'contactUs', [json_encode(['recaptcha' => $this->language->get('error_recaptcha')])]);
			}

			if ($recaptcha_success) {
				// Send the emails
				$this->logger->info('Contact Us submitted from: '.$this->request->postParam('email'));
				$data = [];
				foreach ($site_params->contact_fields as $property => $stuff) {
					$data['fields'][$this->language->get($property)] = $this->request->postParam($property);
				}

				$body = $this->getTemplate('emails/enquiry.txt.php', $data);
				$html = $this->getTemplate('emails/enquiry.html.php', $data);
				$email1 = new Email($this->config);
				$email1->setFromEmail($this->config->siteConfig()->email_addresses->contact_us);
				$email1->setToEmail($this->config->siteConfig()->email_addresses->contact_us);
				$email1->setSubject($this->config->siteConfig()->name.': Website Enquiry');
				$email1->setBodyTemplate($body);
				$email1->setHtmlTemplate($html);

				$body = $this->getTemplate('emails/enquiry_customer.txt.php', $data);
				$html = $this->getTemplate('emails/enquiry_customer.html.php', $data);
				$email2 = new Email($this->config);
				$email2->setFromEmail($this->config->siteConfig()->email_addresses->contact_us);
				$email2->setToEmail($this->request->postParam('email'));
				$email2->setSubject($this->config->siteConfig()->name.': Website Enquiry');
				$email2->setBodyTemplate($body);
				$email2->setHtmlTemplate($html);

				if ($email1->send() && $email2->send()) {
					throw new RedirectException($this->url->getUrl('Root', 'contactUsSent'));
				}
				else {
					throw new SoftRedirectException($this->url->getControllerClass('Root'), 'error500');
				}
			}
		}

		throw new SoftRedirectException(__CLASS__, 'contactUs', [json_encode($form->getErrors())]);
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
		http_response_code(401);
		header("HTTP/1.1 401 Permission Denied");
		$template = $this->getTemplate('pages/error_401.php');
		$this->response->setContent($template->render());
	}

	public function error404() {
		$this->logger->info('Return error 404');
		$this->language->loadLanguageFile('error.php');
		http_response_code(404);
		header("HTTP/1.1 404 Not Found");
		$template = $this->getTemplate('pages/error_404.php');
		$this->response->setContent($template->render());
	}

	public function error500() {
		$this->logger->info('Return error 500');
		$this->language->loadLanguageFile('error.php');
		http_response_code(500);
		header("HTTP/1.1 500 Internal Server Error");
		$template = $this->getTemplate('pages/error_500.php');
		$this->response->setContent($template->render());
	}

	public function getAllMethods() {
		$methods = parent::getAllMethods();
		$url_map = $this->url->getUrlMap();
		if (isset($url_map['forward']['Root'])) {
			$controller_map = $url_map['forward']['Root'];
			if (isset($controller_map['methods'])) {
				foreach ($controller_map['methods'] as $method => $data) {
					if (!in_array($method, $methods)) {
						$methods[] = $method;
					}
				}
			}
		}
		return $methods;
	}
}
