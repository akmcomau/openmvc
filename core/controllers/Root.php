<?php

namespace core\controllers;

use core\classes\exceptions\SoftRedirectException;
use core\classes\exceptions\RedirectException;
use core\classes\exceptions\TemplateException;
use core\classes\renderable\Controller;
use core\classes\URL;

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

		$page_name = str_replace('-', '_', $page_name);

		try {
			$template = $this->getTemplate("pages/misc/$page_name.php");
			$this->response->setContent('<div class="container">'.$template->render().'</div>');
		}
		catch (TemplateException $ex) {
			$this->logger->debug('Misc page template not found');
			throw new SoftRedirectException(__CLASS__, 'error_404');
		}
	}

	public function terms() {
		$template = $this->getTemplate('pages/terms.php');
		$this->response->setContent($template->render());
	}

	public function privacy() {
		$template = $this->getTemplate('pages/privacy.php');
		$this->response->setContent($template->render());
	}

	public function aboutUs() {
		$template = $this->getTemplate('pages/about_us.php');
		$this->response->setContent($template->render());
	}

	public function contactUs($status = NULL) {
		$site_params = $this->config->siteConfig();
		if ($this->request->postParam('send_message') == 1) {
			$headers  = "From: {$this->request->postParam('email')}\n";
			$headers .= "Content-Type: text/html";

			$subject = "Website Enquiry";

			$body  = "An enquiry has been submitted.<br/><br/>";
			$body .= "Name : " . $this->request->postParam('name') . "<br/>";
			$body .= "Email: " . $this->request->postParam('email') . "<br/>";
			$body .= "Desc : " . $this->request->postParam('description') . "<br/><br/>";
			$body .= "Regards,<br/>";
			$body .= "AKM Computer Services<br/><br/>";

			if (mail($site_params->email_addresses->contact_us, $subject, $body, $headers)) {
				throw new RedirectException($this->request->currentURL([]).'/success');
			}
			else {
				throw new RedirectException($this->request->currentURL([]).'/error');
			}
		}

		$message = '';
		if ($status == 'success') {
			$message = 'Your Message has been sent.';
		}
		elseif ($status == 'error') {
			$message = 'An error occured during sending you message.  Please try again later.';
		}

		$data = [
			'message' => $message,
			'contact_us_email' => $site_params->email_addresses->contact_us,
		];

		$template = $this->getTemplate('pages/contact_us.php', $data);
		$this->response->setContent($template->render());
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