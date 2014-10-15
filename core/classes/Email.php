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
	protected $attachments = [];

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

	public function attach($file_name, $file_path, $file_type) {
		$attachment = chunk_split(base64_encode(file_get_contents($file_path)));
		$this->attachments[] = [
			'content' => $attachment,
			'type'    => $file_type,
			'name'    => $file_name,
		];
	}

	public function send() {
		$random_hash = md5(date('r', time()));
		$headers = "From: ".$this->from_email."\r\nReply-To: ".$this->from_email;
		$headers .= "\r\nContent-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\"";

		ob_start();
?>
--PHP-mixed-<?php echo $random_hash; ?>

Content-Type: multipart/alternative; boundary="PHP-alt-<?php echo $random_hash; ?>"

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

		if (count($this->attachments)) {
			foreach ($this->attachments as $attachment) {
?>

--PHP-mixed-<?php echo $random_hash; ?>

Content-Type: <?php echo $attachment['type']; ?>; name="<?php echo $attachment['name']; ?>"
Content-Transfer-Encoding: base64
Content-Disposition: attachment

<?php echo $attachment['content']; ?>
<?php
			}
		}

?>
--PHP-mixed-<?php echo $random_hash; ?>--
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
