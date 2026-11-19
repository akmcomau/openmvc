<?php

namespace core\classes;

class Email {
	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The logger object
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * The email address to send this email to
	 * @var string $to_email
	 */
	protected $to_email = '';

	/**
	 * The email address to CC this email to
	 * @var string $to_email
	 */
	protected $cc_email = '';

	/**
	 * The email address to BCC this email to
	 * @var string $to_email
	 */
	protected $bcc_email = '';

	/**
	 * The email address this email is from
	 * @var string $from_email
	 */
	protected $from_email;

	/**
	 * The subject of this email
	 * @var string $subject
	 */
	protected $subject = '';

	/**
	 * The template to use for the text body of this email
	 * @var string $body_template
	 */
	protected $body_template = NULL;

	/**
	 * The content to use for the text body of this email
	 * @var string $body_content
	 */
	protected $body_content = NULL;

	/**
	 * The template to use for the HTML body of this email
	 * @var string $html_template
	 */
	protected $html_template = NULL;

	/**
	 * The content to use for the HTML body of this email
	 * @var string $html_content
	 */
	protected $html_content = NULL;

	/**
	 * Holds all the attachments on the email
	 * @var string $attachments
	 */
	protected $attachments = [];

	/**
	 * Constructor
	 * @param $config   \b Config The configuration object
	 */
	public function __construct(Config $config) {
		$this->config = $config;
		$this->from_email = $config->siteConfig()->email_addresses->from;
		$this->logger = Logger::getLogger(__CLASS__);
	}

	/**
	 * Strip CR/LF from a value bound for an email header, to prevent header
	 * injection (extra headers, e.g. additional Bcc:, smuggled in via a
	 * newline in a user-supplied address/name/subject).
	 * @param $value \b string The value to sanitize
	 */
	protected function stripHeaderInjection($value) {
		return str_replace(["\r", "\n"], '', $value);
	}

	/**
	 * Create an RFC2822 email address
	 * @param $email \b string The receipient's email address
	 * @param $name  \b string The receipient's name
	 */
	public function createEmailAddress($email, $name) {
		// @TODO Make the email address compliant
		return "$name <$email>";
	}

	/**
	 * Set receipient of the email
	 * @param $to_email \b mixed The TO email address or an array or email addresses
	 */
	public function setToEmail($to_email) {
		if (is_array($to_email)) {
			$this->to_email = join(',', array_map([$this, 'stripHeaderInjection'], $to_email));
		}
		else {
			$this->to_email = $this->stripHeaderInjection($to_email);
		}
	}

	/**
	 * Add a receipient of the email
	 * @param $to_email \b string The TO email address
	 */
	public function addToEmail($to_email) {
		if (strlen($this->to_email) > 0) $this->to_email .= ',';
		$this->to_email .= $this->stripHeaderInjection($to_email);
	}

	/**
	 * Set CC of the email
	 * @param $cc_email \b mixed The CC email address or an array or email addresses
	 */
	public function setCcEmail($cc_email) {
		if (is_array($cc_email)) {
			$this->cc_email = join(',', array_map([$this, 'stripHeaderInjection'], $cc_email));
		}
		else {
			$this->cc_email = $this->stripHeaderInjection($cc_email);
		}
	}

	/**
	 * Add a receipient of the email
	 * @param $cc_email \b string The CC email address
	 */
	public function addCcEmail($cc_email) {
		if (strlen($this->cc_email) > 0) $this->cc_email .= ',';
		$this->cc_email .= $this->stripHeaderInjection($cc_email);
	}

	/**
	 * Set receipient of the email
	 * @param $bcc_email \b mixed The BCC email address or an array or email addresses
	 */
	public function setBccEmail($bcc_email) {
		if (is_array($bcc_email)) {
			$this->bcc_email = join(',', array_map([$this, 'stripHeaderInjection'], $bcc_email));
		}
		else {
			$this->bcc_email = $this->stripHeaderInjection($bcc_email);
		}
	}

	/**
	 * Add a receipient of the email
	 * @param $bcc_email \b string The BCC email address
	 */
	public function addBccEmail($bcc_email) {
		if (strlen($this->bcc_email) > 0) $this->bcc_email .= ',';
		$this->bcc_email .= $this->stripHeaderInjection($bcc_email);
	}

	/**
	 * Set the from address of the email
	 * @param $from_email \b string The FROM email address
	 */
	public function setFromEmail($from_email) {
		$this->from_email = $this->stripHeaderInjection($from_email);
	}

	/**
	 * Set the email's subject
	 * @param $subject \b string The email's subject
	 */
	public function setSubject($subject) {
		$this->subject = $this->stripHeaderInjection($subject);
	}

	/**
	 * Set the text message template
	 * @param $body_template \b Template The Template object for the text message
	 */
	public function setBodyTemplate(Template $body_template) {
		$this->body_template = $body_template;
	}

	/**
	 * Set the text message content
	 * @param $body_content \b String The content for the text message
	 */
	public function setBodyContent($body_content) {
		$this->body_content = $body_content;
	}

	/**
	 * Set the HTML message template
	 * @param $html_template \b Template  The Template object for the HTML message
	 */
	public function setHtmlTemplate(Template $html_template) {
		$this->html_template = $html_template;
	}

	/**
	 * Set the HTML message content
	 * @param $html_content \b String  The content for the HTML message
	 */
	public function setHtmlContent($html_content) {
		$this->html_content = $html_content;
	}

	/**
	 * Attach a file to the email
	 * @param $file_name \b string The filename of the attachment
	 * @param $file_path \b string The path to the file to attach
	 * @param $mime_type \b string The mime type of the file
	 */
	public function attach($file_name, $file_path, $mime_type) {
		$attachment = chunk_split(base64_encode(file_get_contents($file_path)));
		$this->attachments[] = [
			'content' => $attachment,
			'type'    => $mime_type,
			'name'    => $file_name,
		];
	}

	/**
	 * Attach a file to the email, using a string for the file content
	 * @param $file_name    \b string The filename of the attachment
	 * @param $file_content \b string The content of the file
	 * @param $mime_type     \b string The mime type of the file
	 */
	public function attachContent($file_name, $file_content, $mime_type) {
		$attachment = chunk_split(base64_encode($file_content));
		$this->attachments[] = [
			'content' => $attachment,
			'type'    => $mime_type,
			'name'    => $file_name,
		];
	}

	/**
	 * Send the email
	 * @return \b boolean TRUE if the email was successfully sent or FALSE if an error occurred
	 */
	public function send() {
		// unpredictable, so message content can't contain (and so break out of) the boundary
		$random_hash = bin2hex(random_bytes(16));
		$mixed = 'PHP-mixed-'.$random_hash;
		$alt = 'PHP-alt-'.$random_hash;

		$headers = '';
		if ($this->cc_email)  $headers .= 'CC: '.  $this->cc_email  . "\r\n";
		if ($this->bcc_email) $headers .= 'BCC: '. $this->bcc_email . "\r\n";

		$headers .= "From: ".$this->from_email."\r\n";
		$headers .= "Reply-To: ".$this->from_email."\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: multipart/mixed; boundary=\"".$mixed."\"";

		// the text and HTML alternatives
		$alternatives = [];
		if ($this->body_template || $this->body_content) {
			$text = '';
			if ($this->config->siteConfig()->force_email_rcpt) {
				$text .= 'Email recipient overridden, original recipient: '.$this->to_email."\n\n";
			}
			$text .= $this->body_template ? $this->body_template->render() : $this->body_content;
			$alternatives[] = ['text/plain', $text];
		}
		if ($this->html_template || $this->html_content) {
			$html = '';
			if ($this->config->siteConfig()->force_email_rcpt) {
				$html .= '<strong>Email recipient overridden, original recipient: '.htmlspecialchars($this->to_email).'</strong><br /><br />';
			}
			$html .= $this->html_template ? $this->html_template->render() : $this->html_content;
			$alternatives[] = ['text/html', $html];
		}

		$message = '';
		if (count($alternatives)) {
			$message .= "--$mixed\r\n";
			$message .= "Content-Type: multipart/alternative; boundary=\"$alt\"\r\n\r\n";
			foreach ($alternatives as list($type, $content)) {
				// The content is UTF-8, which isn't valid as 7bit; quoted-printable
				// keeps it intact and keeps line lengths within the SMTP limit
				$message .= "--$alt\r\n";
				$message .= "Content-Type: $type; charset=\"UTF-8\"\r\n";
				$message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
				$message .= quoted_printable_encode(str_replace(["\r\n", "\r"], "\n", (string)$content))."\r\n";
			}
			$message .= "--$alt--\r\n";
		}

		foreach ($this->attachments as $attachment) {
			// these go inside header parameters, so no CR/LF, quotes or backslashes
			$name = str_replace(["\r", "\n", '"', '\\'], '', (string)$attachment['name']);
			$type = preg_replace('/[^A-Za-z0-9!#$&^_.+\/-]/', '', (string)$attachment['type']);
			$message .= "--$mixed\r\n";
			$message .= "Content-Type: $type; name=\"$name\"\r\n";
			$message .= "Content-Transfer-Encoding: base64\r\n";
			$message .= "Content-Disposition: attachment; filename=\"$name\"\r\n\r\n";
			$message .= $attachment['content']."\r\n";
		}

		$message .= "--$mixed--\r\n";

		if ($this->config->siteConfig()->force_email_rcpt) {
			$this->to_email = $this->config->siteConfig()->force_email_rcpt;
		}

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
