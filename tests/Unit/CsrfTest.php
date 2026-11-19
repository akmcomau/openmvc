<?php

namespace tests\Unit;

use ReflectionMethod;
use tests\FrameworkTestCase;
use core\classes\Csrf;
use core\classes\Dispatcher;

class CsrfTest extends FrameworkTestCase {

	const SERVER_KEYS = ['REQUEST_METHOD', 'HTTP_X_CSRF_TOKEN', 'HTTP_SEC_FETCH_SITE', 'HTTP_ORIGIN', 'HTTP_REFERER', 'HTTP_HOST'];

	protected $saved_server = [];

	protected function setUp(): void {
		parent::setUp();
		foreach (self::SERVER_KEYS as $key) {
			$this->saved_server[$key] = $_SERVER[$key] ?? NULL;
			unset($_SERVER[$key]);
		}
		$_SERVER['HTTP_HOST'] = 'test.local';
	}

	protected function tearDown(): void {
		foreach ($this->saved_server as $key => $value) {
			if (is_null($value)) unset($_SERVER[$key]); else $_SERVER[$key] = $value;
		}
		parent::tearDown();
	}

	protected function setToken(string $token = 'abc123'): string {
		$_SESSION[Csrf::SESSION_KEY] = $token;
		return $token;
	}

	// -- tokens --

	public function testTokenMustMatchSession(): void {
		$this->setToken('right');
		$_POST[Csrf::FIELD] = 'wrong';
		$this->assertFalse(Csrf::hasValidToken());

		$_POST[Csrf::FIELD] = 'right';
		$this->assertTrue(Csrf::hasValidToken());
	}

	public function testTokenAcceptedFromHeader(): void {
		$this->setToken('right');
		$_SERVER['HTTP_X_CSRF_TOKEN'] = 'right';
		$this->assertTrue(Csrf::hasValidToken());
	}

	public function testNoSessionTokenMeansNoValidToken(): void {
		$_POST[Csrf::FIELD] = '';
		$this->assertFalse(Csrf::hasValidToken());
	}

	public function testArrayTokenIsRejected(): void {
		$this->setToken('right');
		$_POST[Csrf::FIELD] = ['right'];
		$this->assertFalse(Csrf::hasValidToken());
	}

	// -- same origin --

	public function testFetchMetadataSameOriginAllowed(): void {
		$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
		$this->assertTrue(Csrf::isSameOrigin());
		$_SERVER['HTTP_SEC_FETCH_SITE'] = 'none';
		$this->assertTrue(Csrf::isSameOrigin());
	}

	public function testFetchMetadataCrossSiteRejectedEvenWithMatchingReferer(): void {
		$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
		$_SERVER['HTTP_REFERER'] = 'http://test.local/page';
		$this->assertFalse(Csrf::isSameOrigin());
	}

	public function testRefererFallback(): void {
		$_SERVER['HTTP_REFERER'] = 'https://test.local/devices/view/1';
		$this->assertTrue(Csrf::isSameOrigin());
		$_SERVER['HTTP_REFERER'] = 'https://test.local.evil.com/x';
		$this->assertFalse(Csrf::isSameOrigin());
	}

	public function testNoOriginEvidenceIsNotSameOrigin(): void {
		$this->assertFalse(Csrf::isSameOrigin());
	}

	// -- injection --

	public function testInjectAddsTokenToPostFormsOnly(): void {
		session_status() === PHP_SESSION_ACTIVE || @session_start();
		$token = Csrf::getToken();
		$html = '<html><head><title>x</title></head><body>'
			.'<form method="post" action="/a"><input name="x"></form>'
			.'<form method="get" action="/search"></form></body></html>';

		$out = Csrf::injectIntoHtml($html);

		$this->assertSame(1, substr_count($out, 'name="csrf_token" value="'.$token.'"'));
		$this->assertStringContainsString('<form method="post" action="/a"><input type="hidden" name="csrf_token"', $out);
		$this->assertStringContainsString('<meta name="csrf-token"', $out);
		$this->assertStringContainsString('X-CSRF-Token', $out);
	}

	public function testInjectLeavesFragmentsAndJsonAlone(): void {
		session_status() === PHP_SESSION_ACTIVE || @session_start();
		$json = json_encode(['html' => '<form method="post"></form>']);
		$this->assertSame($json, Csrf::injectIntoHtml($json));
	}

	// -- dispatcher policy --

	protected function checkCsrf($controller, string $method): bool {
		$dispatcher = new Dispatcher($this->makeConfig());
		$check = new ReflectionMethod($dispatcher, 'checkCsrf');
		$check->setAccessible(TRUE);
		return $check->invoke($dispatcher, $controller, $method);
	}

	protected function fakeController(array $state_changing = [], array $exempt = []) {
		return new class($state_changing, $exempt) {
			public function __construct(public array $state_changing, public array $exempt) {}
			public function isStateChangingMethod($m) { return in_array($m, $this->state_changing, TRUE); }
			public function isCsrfExempt($m) { return in_array('*', $this->exempt, TRUE) || in_array($m, $this->exempt, TRUE); }
		};
	}

	public function testPostWithoutTokenIsRejected(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->setToken('right');
		$this->assertFalse($this->checkCsrf($this->fakeController(), 'edit'));

		$_POST[Csrf::FIELD] = 'right';
		$this->assertTrue($this->checkCsrf($this->fakeController(), 'edit'));
	}

	public function testExemptMethodSkipsCheck(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue($this->checkCsrf($this->fakeController([], ['*']), 'uploadData'));
	}

	public function testPlainGetIsAllowed(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertTrue($this->checkCsrf($this->fakeController(), 'view'));
	}

	public function testStateChangingGetNeedsSameOriginOrToken(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$controller = $this->fakeController(['delete']);

		$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
		$this->assertFalse($this->checkCsrf($controller, 'delete'));

		$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
		$this->assertTrue($this->checkCsrf($controller, 'delete'));

		$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
		$this->setToken('right');
		$_GET[Csrf::FIELD] = 'right';
		$this->assertTrue($this->checkCsrf($controller, 'delete'));
	}
}
