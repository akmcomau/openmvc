<?php

namespace core\controllers;

use core\classes\exceptions\RedirectException;
use core\classes\Controller;
use core\classes\Template;

class Information extends Controller {

	public function index() {
		$template = new Template($this, 'pages/information/homepage.php');
		$this->response->setContent($template->render());
	}

	public function page($page_name = NULL) {
		// go to homepage if there is no page
		if (!$page_name) {
			throw new RedirectException($this->request->getURL());
		}

		$page_name = str_replace('-', '_', $page_name);

		$template = new Template($this, "pages/information/$page_name.php");
		$this->response->setContent($template->render());
	}

	public function contactUs($status = NULL) {
		$site_params = $this->request->getSiteParams();
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
				throw new RedirectException($this->getCurrentURL([]).'/success');
			}
			else {
				throw new RedirectException($this->getCurrentURL([]).'/error');
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

		$template = new Template($this, 'pages/information/contact_us.php', $data);
		$this->response->setContent($template->render());
	}
}