<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use core\classes\LoginThrottle;

class LoginThrottleTest extends TestCase {

	protected function makeThrottle(string $ip = '203.0.113.5'): LoginThrottle {
		// a unique scope per test so counters from other runs can't interfere
		return new LoginThrottle('test_'.bin2hex(random_bytes(6)), $ip);
	}

	public function testBlocksAfterTooManyFailuresForALogin(): void {
		$throttle = $this->makeThrottle();
		for ($i = 0; $i < LoginThrottle::MAX_PER_LOGIN; $i++) {
			$this->assertFalse($throttle->isBlocked('alice'));
			$throttle->recordFailure('alice');
		}
		$this->assertTrue($throttle->isBlocked('alice'));
		$this->assertTrue($throttle->isBlocked('ALICE '), 'login names are normalised');
		$this->assertFalse($throttle->isBlocked('bob'));
	}

	public function testSuccessfulLoginClearsTheLoginCounter(): void {
		$throttle = $this->makeThrottle();
		for ($i = 0; $i < LoginThrottle::MAX_PER_LOGIN; $i++) {
			$throttle->recordFailure('alice');
		}
		$throttle->clear('alice');
		$this->assertFalse($throttle->isBlocked('alice'));
	}

	public function testBlocksAnIpTryingManyLogins(): void {
		$throttle = $this->makeThrottle();
		for ($i = 0; $i < LoginThrottle::MAX_PER_IP; $i++) {
			$throttle->recordFailure('user'.$i);
		}
		$this->assertTrue($throttle->isBlocked('someone-new'));
	}

	public function testNonStringLoginDoesNotError(): void {
		$throttle = $this->makeThrottle();
		$throttle->recordFailure(NULL);
		$this->assertFalse($throttle->isBlocked(NULL));
	}
}
