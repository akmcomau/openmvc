<?php

namespace core\classes;

class Email {
	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The database object
	 * @var Database $database
	 */
	protected $logger;

	protected $to_email;
	protected $from_email;
	protected $subject;
	protected $body_template;
	protected $html_template;

	public function __construct(Config $config) {
		$this->config = $config;
		$this->from_email = $config->siteConfig()->email_addresses->from;
		$this->logger = Logger::getLogger(__CLASS__);
	}

	public function setToEmail($to_email) {
		$this->to_email = $to_email;
	}

	public function setFromEmail($from_email) {
		$this->from_email = $from_email;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
	}

	public function setBodyTemplate($body_template) {
		$this->body_template = $body_template;
	}

	public function setHtmlTemplate($html_template) {
		$this->html_template = $html_template;
	}

	public function send() {
		$random_hash = md5(date('r', time()));
		$headers = "From: ".$this->from_email."\r\nReply-To: ".$this->from_email;
		$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\"";

		ob_start();
?>
--PHP-alt-<?php echo $random_hash; ?>

Content-Type: text/plain; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit

<?php echo $this->body_template->render(); ?>

--PHP-alt-<?php echo $random_hash; ?>

Content-Type: text/html; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit

<?php echo $this->html_template->render(); ?>

--PHP-alt-<?php echo $random_hash; ?>--
<?php
		$message = ob_get_clean();

		if (mail($this->to_email, $this->subject, $message, $headers)) {
			$this->logger->info('Sent email to: '.$this->to_email.' => '.$this->subject);
			return TRUE;
		}
		else {
			$this->logger->error('Failed to send email: '.$this->to_email.' => '.$this->subject);
			return FALSE;
		}
	}
}
