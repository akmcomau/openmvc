<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\URL;

class UrlTest extends FrameworkTestCase {

	protected function makeUrl(): URL {
		return new URL($this->makeConfig());
	}

	public function testGetRelativeUrlForRootIndex(): void {
		$url = $this->makeUrl();
		$this->assertSame('/', $url->getRelativeUrl('Root', 'index'));
	}

	public function testGetRelativeUrlForNonRootController(): void {
		// core/meta/Customer.php defines an SEO alias ('customer', lowercase)
		// for this controller, which getRelativeUrl() prefers over the raw
		// controller name.
		$url = $this->makeUrl();
		$this->assertSame('/customer', $url->getRelativeUrl('Customer', 'index'));
	}

	public function testGetRelativeUrlForNonIndexMethod(): void {
		$url = $this->makeUrl();
		$this->assertSame('/change_password', $url->getRelativeUrl('Root', 'change_password'));
	}

	public function testGetRelativeUrlWithParams(): void {
		$url = $this->makeUrl();
		$this->assertSame('/page/about-us', $url->getRelativeUrl('Root', 'page', ['about-us']));
	}

	public function testGetRelativeUrlReplacesSlashesInParams(): void {
		// getRelativeUrl() replaces '/' with '-' in each param before
		// urlencode()-ing it, rather than percent-encoding the slash - a
		// param can never itself introduce an extra path segment.
		$url = $this->makeUrl();
		$this->assertSame('/page/a-b', $url->getRelativeUrl('Root', 'page', ['a/b']));
	}

	public function testGetRelativeUrlAppendsGetParams(): void {
		// Note: get params are appended after the trailing-slash cleanup
		// and the "empty path defaults to /" check, so a root URL with get
		// params comes back as "?foo=bar" rather than "/?foo=bar".
		$url = $this->makeUrl();
		$relative = $url->getRelativeUrl('Root', 'index', [], ['foo' => 'bar']);
		$this->assertSame('?foo=bar', $relative);
	}

	public function testGetUrlIncludesSiteBaseUrl(): void {
		$url = $this->makeUrl();
		$this->assertSame('http://test.local/customer', $url->getUrl('Customer', 'index'));
	}

	public function testGetControllerClassNameResolvesRegisteredController(): void {
		$url = $this->makeUrl();
		$this->assertSame('Root', $url->getControllerClassName('\core\controllers\Root'));
	}

	public function testGetControllerClassNamePassesThroughUnknownController(): void {
		$url = $this->makeUrl();
		$this->assertSame('NotAController', $url->getControllerClassName('NotAController'));
	}

	public function testListAllControllersIncludesCoreControllers(): void {
		$url = $this->makeUrl();
		$controllers = $url->listAllControllers();

		$this->assertArrayHasKey('Root', $controllers);
		$this->assertArrayHasKey('Customer', $controllers);
		$this->assertArrayHasKey('administrator\Customers', $controllers);
	}

	public function testCanonicalStripsPunctuationAndLowercases(): void {
		$url = $this->makeUrl();
		$this->assertSame('hello-world', $url->canonical('Hello, World!'));
		$this->assertSame('a-b-c', $url->canonical('A/B|C'));
	}

	public function testCanonicalCollapsesRepeatedSeparators(): void {
		$url = $this->makeUrl();
		$this->assertSame('a-b', $url->canonical('A -- B'));
	}

	public function testGetLinkTextDefaultsToControllerAndMethod(): void {
		$url = $this->makeUrl();
		$this->assertSame('SomeController::someMethod', $url->getLinkText('SomeController', 'someMethod'));
	}

	public function testGetStaticUrlPrependsStaticPrefix(): void {
		$url = $this->makeUrl();
		$this->assertSame('http://test.local/static-1/css/style.css', $url->getStaticUrl('/css/style.css'));
	}

	public function testUsingSslReflectsHttpsServerVar(): void {
		$url = $this->makeUrl();
		$this->assertFalse($url->usingSSL());

		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue($url->usingSSL());
		unset($_SERVER['HTTPS']);
	}

	public function testGetMethodMetaTagsFallsBackToSiteName(): void {
		$url = $this->makeUrl();
		$tags = $url->getMethodMetaTags('SomeController', 'someMethod');

		$this->assertArrayHasKey('title', $tags);
		$this->assertNotEmpty($tags['title']);
	}
}
