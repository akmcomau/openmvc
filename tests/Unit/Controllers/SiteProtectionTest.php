<?php

namespace tests\Unit\Controllers;

use tests\FrameworkTestCase;
use core\classes\renderable\Controller;
use core\classes\exceptions\SoftRedirectException;
use tests\fixtures\models\SiteScopedThing;

/**
 * Tests for Controller::siteProtection()/allowedSiteIDs() - the multi-site
 * (multi-tenant) admin scoping boundary used throughout the admin
 * controllers: a scoped admin (admin_site_id set in session) or a
 * single-site install (config siteConfig()->site_id) must not be able to
 * touch a record belonging to a different site.
 */
class SiteProtectionTest extends FrameworkTestCase {

	protected function makeTestController(bool $showAdminLayout = FALSE): Controller {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase();
		$request = $this->makeRequest($config, [], [], [], $database);
		$controller = new Controller($config, $database, $request, new \core\classes\Response());
		$this->setProtectedProperty($controller, 'show_admin_layout', $showAdminLayout);
		return $controller;
	}

	protected function makeScopedThing(int $siteId): SiteScopedThing {
		$thing = new SiteScopedThing($this->makeConfig(), $this->makeMockDatabase());
		$thing->site_id = $siteId;
		return $thing;
	}

	// -- allowedSiteIDs() --

	public function testAllowedSiteIdsPrefersSessionAdminSiteId(): void {
		$controller = $this->makeTestController();
		$_SESSION['admin_site_id'] = 42;

		$this->assertSame([42], $this->callProtectedMethod($controller, 'allowedSiteIDs'));
	}

	public function testAllowedSiteIdsFallsBackToConfigSiteId(): void {
		$controller = $this->makeTestController();
		unset($_SESSION['admin_site_id']);

		// The fixture config sets site_id => 1.
		$this->assertSame([1], $this->callProtectedMethod($controller, 'allowedSiteIDs'));
	}

	// -- siteProtection(): NULL model --

	public function testSiteProtectionOnNullModelRedirectsToRootWhenNotAdminLayout(): void {
		$controller = $this->makeTestController(FALSE);

		try {
			$this->callProtectedMethod($controller, 'siteProtection', [NULL]);
			$this->fail('Expected a SoftRedirectException');
		}
		catch (SoftRedirectException $ex) {
			$this->assertSame('error401', $ex->getMethod());
			$this->assertStringContainsString('Root', $ex->getController());
		}
	}

	public function testSiteProtectionOnNullModelRedirectsToAdministratorWhenAdminLayout(): void {
		$controller = $this->makeTestController(TRUE);

		try {
			$this->callProtectedMethod($controller, 'siteProtection', [NULL]);
			$this->fail('Expected a SoftRedirectException');
		}
		catch (SoftRedirectException $ex) {
			$this->assertSame('error404', $ex->getMethod());
			$this->assertStringContainsString('Administrator', $ex->getController());
		}
	}

	// -- siteProtection(): scoped by session admin_site_id --

	public function testSiteProtectionAllowsMatchingSessionScopedSite(): void {
		$controller = $this->makeTestController();
		$_SESSION['admin_site_id'] = 42;

		// No exception should be thrown.
		$this->callProtectedMethod($controller, 'siteProtection', [$this->makeScopedThing(42)]);
		$this->addToAssertionCount(1);
	}

	public function testSiteProtectionRejectsMismatchedSessionScopedSite(): void {
		$controller = $this->makeTestController(FALSE);
		$_SESSION['admin_site_id'] = 42;

		try {
			$this->callProtectedMethod($controller, 'siteProtection', [$this->makeScopedThing(99)]);
			$this->fail('Expected a SoftRedirectException');
		}
		catch (SoftRedirectException $ex) {
			$this->assertSame('error401', $ex->getMethod());
			$this->assertStringContainsString('Root', $ex->getController());
		}
	}

	public function testSiteProtectionRejectsMismatchedSessionScopedSiteWithAdminLayout(): void {
		$controller = $this->makeTestController(TRUE);
		$_SESSION['admin_site_id'] = 42;

		try {
			$this->callProtectedMethod($controller, 'siteProtection', [$this->makeScopedThing(99)]);
			$this->fail('Expected a SoftRedirectException');
		}
		catch (SoftRedirectException $ex) {
			$this->assertSame('error401', $ex->getMethod());
			$this->assertStringContainsString('Administrator', $ex->getController());
		}
	}

	// -- siteProtection(): falls back to config siteConfig()->site_id
	// when there's no session admin_site_id (e.g. a single-site install) --

	public function testSiteProtectionAllowsMatchingConfigSite(): void {
		$controller = $this->makeTestController();
		unset($_SESSION['admin_site_id']);

		// The fixture config's site_id is 1.
		$this->callProtectedMethod($controller, 'siteProtection', [$this->makeScopedThing(1)]);
		$this->addToAssertionCount(1);
	}

	public function testSiteProtectionRejectsMismatchedConfigSite(): void {
		$controller = $this->makeTestController(FALSE);
		unset($_SESSION['admin_site_id']);

		try {
			$this->callProtectedMethod($controller, 'siteProtection', [$this->makeScopedThing(999)]);
			$this->fail('Expected a SoftRedirectException');
		}
		catch (SoftRedirectException $ex) {
			$this->assertSame('error401', $ex->getMethod());
		}
	}

	// -- siteProtection($model, $method): resolves the target via a method
	// call first (e.g. siteProtection($customer, 'getDevice')) --

	public function testSiteProtectionResolvesModelViaMethodCallAndAllows(): void {
		$controller = $this->makeTestController();
		unset($_SESSION['admin_site_id']);

		$wrapper = new SiteScopedThing($this->makeConfig(), $this->makeMockDatabase());
		$wrapper->setObjectCache('related', $this->makeScopedThing(1));

		$this->callProtectedMethod($controller, 'siteProtection', [$wrapper, 'getRelated']);
		$this->addToAssertionCount(1);
	}

	public function testSiteProtectionResolvesModelViaMethodCallAndRejects(): void {
		$controller = $this->makeTestController(FALSE);
		unset($_SESSION['admin_site_id']);

		$wrapper = new SiteScopedThing($this->makeConfig(), $this->makeMockDatabase());
		$wrapper->setObjectCache('related', $this->makeScopedThing(999));

		$this->expectException(SoftRedirectException::class);
		$this->callProtectedMethod($controller, 'siteProtection', [$wrapper, 'getRelated']);
	}

	public function testSiteProtectionRejectsWhenMethodResolvesToNull(): void {
		$controller = $this->makeTestController(FALSE);

		$wrapper = new SiteScopedThing($this->makeConfig(), $this->makeMockDatabase());
		// 'related' object-cache slot left unset - getRelated() returns NULL.

		$this->expectException(SoftRedirectException::class);
		$this->callProtectedMethod($controller, 'siteProtection', [$wrapper, 'getRelated']);
	}
}
