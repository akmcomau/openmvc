<?php

namespace tests\Unit;

require_once __DIR__.'/../Support/EmailMailStub.php';

use tests\FrameworkTestCase;
use core\classes\Email;
use core\classes\EmailMailStub;

/**
 * Regression tests for the Email header-injection fix: to/cc/bcc/from/
 * subject are all attacker-reachable in places (a contact form, a "from
 * name" field, etc.) and get concatenated directly into the mail() headers
 * string, so a value containing a newline could previously inject extra
 * headers (an extra Bcc:, a forged From:, etc.).
 */
class EmailTest extends FrameworkTestCase {

	protected function tearDown(): void {
		EmailMailStub::reset();
		parent::tearDown();
	}

	protected function makeEmail(): Email {
		return new Email($this->makeConfig());
	}

	// -- setter-level: each setter must strip CR/LF before storing --

	public function testSetToEmailStripsInjectedHeaders(): void {
		$email = $this->makeEmail();
		$email->setToEmail("victim@example.com\r\nBcc: attacker@evil.com");

		$this->assertSame('victim@example.comBcc: attacker@evil.com', $this->getProtectedProperty($email, 'to_email'));
	}

	public function testSetToEmailWithArrayStripsInjectedHeadersFromEachElement(): void {
		$email = $this->makeEmail();
		$email->setToEmail(["a@example.com\r\nBcc: x@evil.com", "b@example.com\nBcc: y@evil.com"]);

		$this->assertSame('a@example.comBcc: x@evil.com,b@example.comBcc: y@evil.com', $this->getProtectedProperty($email, 'to_email'));
	}

	public function testAddToEmailStripsInjectedHeaders(): void {
		$email = $this->makeEmail();
		$email->addToEmail("victim@example.com\r\nBcc: attacker@evil.com");

		$this->assertStringNotContainsString("\r\n", $this->getProtectedProperty($email, 'to_email'));
	}

	public function testSetCcEmailStripsInjectedHeaders(): void {
		$email = $this->makeEmail();
		$email->setCcEmail("victim@example.com\r\nBcc: attacker@evil.com");

		$this->assertStringNotContainsString("\r\n", $this->getProtectedProperty($email, 'cc_email'));
	}

	public function testAddCcEmailStripsInjectedHeaders(): void {
		$email = $this->makeEmail();
		$email->addCcEmail("victim@example.com\nBcc: attacker@evil.com");

		$this->assertStringNotContainsString("\n", $this->getProtectedProperty($email, 'cc_email'));
	}

	public function testSetBccEmailStripsInjectedHeaders(): void {
		$email = $this->makeEmail();
		$email->setBccEmail("victim@example.com\r\nTo: attacker@evil.com");

		$this->assertStringNotContainsString("\r\n", $this->getProtectedProperty($email, 'bcc_email'));
	}

	public function testAddBccEmailStripsInjectedHeaders(): void {
		$email = $this->makeEmail();
		$email->addBccEmail("victim@example.com\rTo: attacker@evil.com");

		$this->assertStringNotContainsString("\r", $this->getProtectedProperty($email, 'bcc_email'));
	}

	public function testSetFromEmailStripsInjectedHeaders(): void {
		$email = $this->makeEmail();
		$email->setFromEmail("forger@example.com\r\nBcc: attacker@evil.com");

		$this->assertStringNotContainsString("\r\n", $this->getProtectedProperty($email, 'from_email'));
	}

	public function testSetSubjectStripsInjectedHeaders(): void {
		$email = $this->makeEmail();
		$email->setSubject("Hello\r\nBcc: attacker@evil.com");

		$this->assertStringNotContainsString("\r\n", $this->getProtectedProperty($email, 'subject'));
	}

	public function testLegitimateValuesAreUnaffected(): void {
		$email = $this->makeEmail();
		$email->setToEmail('normal@example.com');
		$email->setSubject('A perfectly normal subject line');

		$this->assertSame('normal@example.com', $this->getProtectedProperty($email, 'to_email'));
		$this->assertSame('A perfectly normal subject line', $this->getProtectedProperty($email, 'subject'));
	}

	// -- end-to-end: send()'s actual mail() headers must never contain an
	// attacker-injected header, even though this goes through the full
	// header-building code in send(), not just the setters directly. --

	public function testSendNeverProducesInjectedHeadersInFinalMailCall(): void {
		$email = $this->makeEmail();
		$email->setToEmail("victim@example.com\r\nBcc: attacker@evil.com");
		$email->setSubject("Enquiry\r\nX-Injected: evil");
		$email->setBodyContent('Hello');

		$email->send();

		$this->assertNotNull(EmailMailStub::$lastCall, 'mail() was never called');
		$this->assertStringNotContainsString('Bcc: attacker@evil.com', EmailMailStub::$lastCall['headers']);
		$this->assertStringNotContainsString("\r\n", EmailMailStub::$lastCall['to']);
		$this->assertStringNotContainsString("\r\n", EmailMailStub::$lastCall['subject']);
	}

	public function testSendUsesConstructedToSubjectAndHeaders(): void {
		$email = $this->makeEmail();
		$email->setToEmail('someone@example.com');
		$email->setSubject('Hi there');
		$email->setBodyContent('Body text');

		$this->assertTrue($email->send());

		$this->assertSame('someone@example.com', EmailMailStub::$lastCall['to']);
		$this->assertSame('Hi there', EmailMailStub::$lastCall['subject']);
		$this->assertStringContainsString('Body text', EmailMailStub::$lastCall['message']);
	}

	public function testSendReturnsFalseWhenMailFails(): void {
		EmailMailStub::$returnValue = FALSE;
		$email = $this->makeEmail();
		$email->setToEmail('someone@example.com');
		$email->setBodyContent('Body');

		$this->assertFalse($email->send());
	}

	public function testForceEmailRcptOverridesRecipient(): void {
		$config = $this->makeConfig();
		$config->updateSiteConfigParam('force_email_rcpt', 'override@example.com');
		$email = new Email($config);
		$email->setToEmail('someone@example.com');
		$email->setBodyContent('Body');

		$email->send();

		$this->assertSame('override@example.com', EmailMailStub::$lastCall['to']);
	}
}
