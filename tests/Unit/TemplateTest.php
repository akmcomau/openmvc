<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Template;
use core\classes\Language;
use core\classes\exceptions\TemplateException;

/**
 * Tests for core\classes\Template: the theme/default-theme/core-default
 * template file resolution order in getAbsoluteFilename(), and render()'s
 * data/language-string extraction and parent-template chaining.
 */
class TemplateTest extends FrameworkTestCase {

	protected array $writtenFiles = [];

	protected function tearDown(): void {
		foreach ($this->writtenFiles as $file) {
			if (file_exists($file)) unlink($file);
			$dir = dirname($file);
			while (str_starts_with($dir, OPENMVC_ROOT.'/sites/') && @rmdir($dir)) {
				$dir = dirname($dir);
			}
			while (str_starts_with($dir, OPENMVC_ROOT.'/core/themes/') && @rmdir($dir)) {
				$dir = dirname($dir);
			}
		}
		$this->writtenFiles = [];
		parent::tearDown();
	}

	protected function makeLanguage(): Language {
		return new Language($this->makeConfig());
	}

	/** Writes $content to $absolutePath, creating parent dirs, and tracks it for cleanup. */
	protected function writeTemplateFile(string $absolutePath, string $content): void {
		@mkdir(dirname($absolutePath), 0777, TRUE);
		file_put_contents($absolutePath, $content);
		$this->writtenFiles[] = $absolutePath;
	}

	protected function siteThemePath(string $filename): string {
		return OPENMVC_ROOT.'/sites/default/themes/default/templates/'.$filename;
	}

	protected function coreDefaultPath(string $filename): string {
		return OPENMVC_ROOT.'/core/themes/default/templates/'.$filename;
	}

	// -- getAbsoluteFilename() resolution order --

	public function testGetAbsoluteFilenamePrefersSiteThemeOverCoreDefault(): void {
		$name = 'test_template_'.uniqid().'.php';
		$this->writeTemplateFile($this->siteThemePath($name), 'theme version');
		$this->writeTemplateFile($this->coreDefaultPath($name), 'core default version');

		$template = new Template($this->makeConfig(), $this->makeLanguage(), $name);

		$this->assertSame($this->siteThemePath($name), realpath($template->getAbsoluteFilename()));
	}

	public function testGetAbsoluteFilenameFallsBackToCoreDefault(): void {
		$name = 'test_template_'.uniqid().'.php';
		$this->writeTemplateFile($this->coreDefaultPath($name), 'core default version');

		$template = new Template($this->makeConfig(), $this->makeLanguage(), $name);

		$this->assertSame($this->coreDefaultPath($name), realpath($template->getAbsoluteFilename()));
	}

	public function testGetAbsoluteFilenameFallsBackToDefaultThemeWhenSiteThemeMissing(): void {
		$name = 'test_template_'.uniqid().'.php';
		$default_theme_path = OPENMVC_ROOT.'/sites/default/themes/__test_alt_theme__/templates/'.$name;
		$this->writeTemplateFile($default_theme_path, 'alt theme version');

		$config = $this->makeConfig();
		$config->updateSiteConfigParam('default_theme', '__test_alt_theme__');
		$template = new Template($config, $this->makeLanguage(), $name);

		// Neither the site's real theme dir nor core's default dir has this
		// file, so resolution must fall through to the default_theme path.
		$this->assertSame($default_theme_path, realpath($template->getAbsoluteFilename()));
	}

	public function testGetAbsoluteFilenameThrowsWhenFileNotFoundAnywhere(): void {
		$template = new Template($this->makeConfig(), $this->makeLanguage(), 'no_such_template_'.uniqid().'.php');

		$this->expectException(TemplateException::class);
		$template->getAbsoluteFilename();
	}

	public function testGetAbsoluteFilenameThrowsWhenNoFilenameSet(): void {
		$template = new Template($this->makeConfig(), $this->makeLanguage(), NULL);

		$this->expectException(TemplateException::class);
		$template->getAbsoluteFilename();
	}

	public function testSetFilenameOverridesConstructorFilename(): void {
		$name = 'test_template_'.uniqid().'.php';
		$this->writeTemplateFile($this->siteThemePath($name), 'content');

		$template = new Template($this->makeConfig(), $this->makeLanguage(), 'irrelevant.php');
		$template->setFilename($name);

		$this->assertSame($this->siteThemePath($name), realpath($template->getAbsoluteFilename()));
	}

	// -- getTemplateContent() --

	public function testGetTemplateContentReturnsRawFileContents(): void {
		$name = 'test_template_'.uniqid().'.php';
		$this->writeTemplateFile($this->siteThemePath($name), 'raw <?php /* not executed here */ ?> content');

		$template = new Template($this->makeConfig(), $this->makeLanguage(), $name);

		$this->assertSame('raw <?php /* not executed here */ ?> content', $template->getTemplateContent());
	}

	// -- render() --

	public function testRenderExtractsDataIntoTemplateScope(): void {
		$name = 'test_template_'.uniqid().'.php';
		$this->writeTemplateFile($this->siteThemePath($name), '<?php echo "Value: ".$some_var; ?>');

		$template = new Template($this->makeConfig(), $this->makeLanguage(), $name, ['some_var' => 'hello']);

		$this->assertSame('Value: hello', $template->render());
	}

	public function testRenderExtractsLanguageStringsWithTextPrefix(): void {
		$name = 'test_template_'.uniqid().'.php';
		$this->writeTemplateFile($this->siteThemePath($name), '<?php echo $text_greeting; ?>');

		$language = $this->makeLanguage();
		$this->setProtectedProperty($language, 'strings', ['greeting' => 'Hello there']);

		$template = new Template($this->makeConfig(), $language, $name);

		$this->assertSame('Hello there', $template->render());
	}

	public function testRenderChainsThroughParentTemplate(): void {
		$child_name = 'test_child_'.uniqid().'.php';
		$parent_name = 'test_parent_'.uniqid().'.php';
		$this->writeTemplateFile($this->siteThemePath($child_name), '<?php echo "Child:".$some_var; ?>');
		$this->writeTemplateFile($this->siteThemePath($parent_name), '<?php echo "Parent[".$child_content."]"; ?>');

		$template = new Template($this->makeConfig(), $this->makeLanguage(), $child_name, ['some_var' => 'X']);
		$template->setParentTemplate($parent_name);

		$this->assertSame('Parent[Child:X]', $template->render());
	}

	public function testRenderSetsPageClassAndSiteUrlFromConfig(): void {
		$name = 'test_template_'.uniqid().'.php';
		$this->writeTemplateFile($this->siteThemePath($name), '<?php echo $page_class."|".$site_url; ?>');

		$template = new Template($this->makeConfig(), $this->makeLanguage(), $name);
		$result = $template->render();

		$this->assertStringContainsString('|', $result);
	}

	// -- includeTemplate() --

	public function testIncludeTemplateRequiresResolvedFileWithExtractedData(): void {
		$name = 'test_template_'.uniqid().'.php';
		$this->writeTemplateFile($this->siteThemePath($name), '<?php echo "Included:".$some_var; ?>');

		$template = new Template($this->makeConfig(), $this->makeLanguage(), NULL, ['some_var' => 'Y']);

		ob_start();
		$template->includeTemplate($name);
		$output = ob_get_clean();

		$this->assertSame('Included:Y', $output);
	}

	// -- accessors --

	public function testGetDataReturnsConstructorData(): void {
		$template = new Template($this->makeConfig(), $this->makeLanguage(), 'x.php', ['a' => 1]);
		$this->assertSame(['a' => 1], $template->getData());
	}

	public function testSetDataOverridesData(): void {
		$template = new Template($this->makeConfig(), $this->makeLanguage(), 'x.php', ['a' => 1]);
		$template->setData(['b' => 2]);
		$this->assertSame(['b' => 2], $template->getData());
	}

	public function testGetFilenameReturnsConstructorFilename(): void {
		$template = new Template($this->makeConfig(), $this->makeLanguage(), 'some_file.php');
		$this->assertSame('some_file.php', $template->getFilename());
	}
}
