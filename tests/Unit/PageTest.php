<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Page;

/**
 * Regression tests for Page::update()'s stored-RCE fix: saved page content
 * is written verbatim to a .php file that Template::render() later
 * require()s, so a PHP open tag in the content would previously execute as
 * code on every page view. Content is now neutralised before it's written.
 */
class PageTest extends FrameworkTestCase {

	protected array $writtenFiles = [];

	protected function tearDown(): void {
		foreach ($this->writtenFiles as $file) {
			if (file_exists($file)) unlink($file);
			$dir = dirname($file);
			while (str_starts_with($dir, OPENMVC_ROOT.'/sites/') && @rmdir($dir)) {
				$dir = dirname($dir);
			}
		}
		$this->writtenFiles = [];
		parent::tearDown();
	}

	protected function makePage(): Page {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase();
		return new Page($config, $database);
	}

	protected function themeFilePath(string $method): string {
		return OPENMVC_ROOT.'/sites/default/themes/default/templates/pages/misc/'.$method.'.php';
	}

	protected function metaFilePath(string $controller): string {
		return OPENMVC_ROOT.'/sites/default/meta/'.$controller.'.php';
	}

	protected function updateData(array $overrides = []): array {
		return array_merge([
			'controller'       => 'Root',
			'controller_alias' => 'root',
			'method'           => 'test-page-'.uniqid(),
			'misc_page'        => TRUE,
			'method_alias'     => 'test-alias',
			'link_text'        => 'Test Page',
			'meta_tags'        => [],
			'parent_template'  => '',
			'banner_image'     => '',
			'category'         => '',
			'content'          => 'Hello, world.',
		], $overrides);
	}

	// -- stored-RCE fix --

	public function testUpdateNeutralisesPhpOpenTagInContent(): void {
		$page = $this->makePage();
		$data = $this->updateData(['content' => '<?php system($_GET["c"]); ?>Hello']);
		$this->writtenFiles[] = $this->themeFilePath($data['method']);
		$this->writtenFiles[] = $this->metaFilePath($data['controller']);

		$page->update($data, TRUE);

		$written = file_get_contents($this->themeFilePath($data['method']));
		$this->assertStringNotContainsString('<?php', $written);
		$this->assertStringContainsString('&lt;?php', $written);
	}

	public function testUpdateNeutralisesShortEchoTag(): void {
		$page = $this->makePage();
		$data = $this->updateData(['content' => '<?= system($_GET["c"]) ?>']);
		$this->writtenFiles[] = $this->themeFilePath($data['method']);
		$this->writtenFiles[] = $this->metaFilePath($data['controller']);

		$page->update($data, TRUE);

		$written = file_get_contents($this->themeFilePath($data['method']));
		$this->assertStringNotContainsString('<?=', $written);
	}

	public function testUpdateNeutralisesBarePhpOpenTag(): void {
		$page = $this->makePage();
		$data = $this->updateData(['content' => '<? system($_GET["c"]); ?>']);
		$this->writtenFiles[] = $this->themeFilePath($data['method']);
		$this->writtenFiles[] = $this->metaFilePath($data['controller']);

		$page->update($data, TRUE);

		$written = file_get_contents($this->themeFilePath($data['method']));
		$this->assertDoesNotMatchRegularExpression('/<\?(?!xml)/i', $written);
	}

	public function testUpdatePreservesXmlProcessingInstructions(): void {
		// e.g. an XML prologue (<?xml ... "?" ">") in embedded SVG content
		// should not be mangled - only actual PHP open tags are neutralised.
		$page = $this->makePage();
		$data = $this->updateData(['content' => '<?xml version="1.0"?><svg></svg>']);
		$this->writtenFiles[] = $this->themeFilePath($data['method']);
		$this->writtenFiles[] = $this->metaFilePath($data['controller']);

		$page->update($data, TRUE);

		$written = file_get_contents($this->themeFilePath($data['method']));
		$this->assertStringContainsString('<?xml version="1.0"?>', $written);
	}

	public function testWrittenFileCannotExecuteAsPhp(): void {
		// The most direct proof: actually require() the saved file (as
		// Template::render() would) and confirm no code runs - if it did,
		// this constant would get defined.
		$page = $this->makePage();
		$data = $this->updateData(['content' => '<?php define("PAGE_TEST_RCE_MARKER", TRUE); ?>Safe content']);
		$path = $this->themeFilePath($data['method']);
		$this->writtenFiles[] = $path;
		$this->writtenFiles[] = $this->metaFilePath($data['controller']);

		$page->update($data, TRUE);

		ob_start();
		require $path;
		ob_get_clean();

		$this->assertFalse(defined('PAGE_TEST_RCE_MARKER'));
	}

	// -- other update() behaviour --

	public function testUpdateCollapsesMultipleLineBreaks(): void {
		$page = $this->makePage();
		$data = $this->updateData(['content' => "Line one\n\n\n\nLine two"]);
		$this->writtenFiles[] = $this->themeFilePath($data['method']);
		$this->writtenFiles[] = $this->metaFilePath($data['controller']);

		$page->update($data, TRUE);

		$written = file_get_contents($this->themeFilePath($data['method']));
		$this->assertSame("Line one\nLine two", $written);
	}

	public function testUpdateRewritesAbsoluteHrefToRelative(): void {
		$page = $this->makePage();
		$domain = FrameworkTestCase::TEST_DOMAIN;
		$data = $this->updateData(['content' => '<a href="http://'.$domain.'/some/page">link</a>']);
		$this->writtenFiles[] = $this->themeFilePath($data['method']);
		$this->writtenFiles[] = $this->metaFilePath($data['controller']);

		$page->update($data, TRUE);

		$written = file_get_contents($this->themeFilePath($data['method']));
		$this->assertStringContainsString('href="/some/page"', $written);
		$this->assertStringNotContainsString('http://', $written);
	}

	public function testUpdateThrowsForUnknownController(): void {
		$page = $this->makePage();
		$this->expectException(\ErrorException::class);
		$page->update($this->updateData(['controller' => 'NoSuchController']), TRUE);
	}

	public function testUpdateReturnsFalseWhenNotOverwritingExistingMethod(): void {
		$page = $this->makePage();
		$data = $this->updateData();
		$this->writtenFiles[] = $this->themeFilePath($data['method']);
		$this->writtenFiles[] = $this->metaFilePath($data['controller']);

		// First save creates the method entry.
		$page->update($data, TRUE);
		// Second call without overwrite must refuse.
		$result = $page->update($data, FALSE);

		$this->assertFalse($result);
	}

	// -- getPage() --

	public function testGetPageReturnsDefaultWhenControllerOrMethodMissing(): void {
		$page = $this->makePage();
		$result = $page->getPage();

		$this->assertSame('default', $result['type']);
		$this->assertSame('Root', $result['controller']);
		$this->assertTrue($result['misc_page']);
	}

	public function testGetPageBuildsExpectedShape(): void {
		$page = $this->makePage();
		$result = $page->getPage('Root', 'index');

		$this->assertSame('Root', $result['controller']);
		$this->assertSame('index', $result['method']);
		$this->assertArrayHasKey('meta_tags', $result);
		$this->assertArrayHasKey('url', $result);
	}
}
