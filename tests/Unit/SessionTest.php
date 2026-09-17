<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use core\classes\Session;

class SessionTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$_SESSION = [];
	}

	public function testSetAndGetFlatValue(): void {
		$session = new Session();
		$session->set('name', 'value');

		$this->assertSame('value', $session->get('name'));
		$this->assertSame('value', $_SESSION['name']);
	}

	public function testGetUndefinedFlatValueReturnsNull(): void {
		$session = new Session();
		$this->assertNull($session->get('missing'));
	}

	public function testSetAndGetNestedValue(): void {
		$session = new Session();
		$session->set(['authentication', 'customer', 'id'], 42);

		$this->assertSame(42, $session->get(['authentication', 'customer', 'id']));
		$this->assertSame(42, $_SESSION['authentication']['customer']['id']);
	}

	public function testGetNestedValueWithMissingIntermediateKeyReturnsNull(): void {
		$session = new Session();
		$session->set(['a', 'b'], 'value');

		$this->assertNull($session->get(['a', 'x', 'y']));
	}

	public function testDeleteFlatValue(): void {
		$session = new Session();
		$session->set('name', 'value');
		$session->delete('name');

		$this->assertNull($session->get('name'));
		$this->assertArrayNotHasKey('name', $_SESSION);
	}

	public function testDeleteNestedValueOnlyRemovesTargetKey(): void {
		$session = new Session();
		$session->set(['authentication', 'customer'], ['id' => 1]);
		$session->set(['authentication', 'administrator'], ['id' => 2]);
		$session->set('unrelated', 'keep-me');

		$session->delete(['authentication', 'customer']);

		$this->assertNull($session->get(['authentication', 'customer']));
		$this->assertSame(['id' => 2], $session->get(['authentication', 'administrator']));
		$this->assertSame('keep-me', $session->get('unrelated'));
	}

	public function testDeleteNestedValueWithMissingPathIsANoop(): void {
		$session = new Session();
		$session->set('unrelated', 'keep-me');

		// Should not throw or warn, and must leave other data untouched.
		$session->delete(['does', 'not', 'exist']);

		$this->assertSame('keep-me', $session->get('unrelated'));
	}

	public function testDeleteDoesNotExecuteArbitraryCode(): void {
		// Regression test for the previous eval()-based implementation of
		// Session::delete(): a value containing PHP-string-breaking
		// characters must be treated as a literal array key, never as code.
		$session = new Session();
		$payload = "']); system('id'); //";
		$session->set(['bucket', $payload], 'value');

		$session->delete(['bucket', $payload]);

		$this->assertNull($session->get(['bucket', $payload]));
	}
}
