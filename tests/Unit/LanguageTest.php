<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Language;
use core\classes\exceptions\LanguageException;

class LanguageTest extends FrameworkTestCase {

	protected array $writtenFiles = [];

	protected function tearDown(): void {
		foreach ($this->writtenFiles as $file) {
			if (file_exists($file)) unlink($file);
			$dir = dirname($file);
			// Best-effort cleanup of the directories updateFile() creates.
			while (str_starts_with($dir, OPENMVC_ROOT.'/sites/') && @rmdir($dir)) {
				$dir = dirname($dir);
			}
		}
		$this->writtenFiles = [];
		parent::tearDown();
	}

	protected function makeLanguage(): Language {
		return new Language($this->makeConfig());
	}

	public function testGetLanguageReturnsConfiguredLanguage(): void {
		$language = $this->makeLanguage();
		$this->assertSame('en', $language->getLanguage());
	}

	public function testGetThrowsForUndefinedTag(): void {
		$language = $this->makeLanguage();
		$this->expectException(LanguageException::class);
		$language->get('no_such_tag');
	}

	public function testExistsReflectsLoadedStrings(): void {
		$language = $this->makeLanguage();
		$this->assertFalse($language->exists('greeting'));
	}

	public function testLoadLanguageFileMakesStringsAvailable(): void {
		// core/language/en/layout.php ships with the framework itself.
		$language = $this->makeLanguage();
		$language->loadLanguageFile('layout.php');

		$this->assertNotEmpty($language->getStrings());
		$this->assertSame([['layout.php', NULL]], $language->getLoadedFiles());
	}

	public function testGetWithParamsFormatsString(): void {
		// Write a real fixture language file with a placeholder tag via
		// updateFile(), then load it back and exercise get()'s vsprintf()
		// formatting against a real, loaded string.
		$language = $this->makeLanguage();
		$filename = 'test_language_params_'.uniqid().'.php';
		$language->updateFile($filename, ['item_count' => '%1$d items']);
		$this->writtenFiles[] = OPENMVC_ROOT.'/sites/default/language/en/'.$filename;

		$language->loadLanguageFile($filename);

		$this->assertSame('3 items', $language->get('item_count', [3]));
	}

	public function testGetWithoutParamsReturnsStringUnmodified(): void {
		$language = $this->makeLanguage();
		$language->loadLanguageFile('layout.php');

		$this->assertSame('Search', $language->get('search_button'));
	}

	public function testUpdateFileRejectsParentDirectoryTraversal(): void {
		$language = $this->makeLanguage();
		$this->expectException(LanguageException::class);
		$language->updateFile('../../etc/passwant.php', ['tag' => 'value']);
	}

	public function testUpdateFileRejectsTraversalInMiddleSegment(): void {
		$language = $this->makeLanguage();
		$this->expectException(LanguageException::class);
		$language->updateFile('foo/../../bar.php', ['tag' => 'value']);
	}

	public function testUpdateFileRejectsNonPhpExtension(): void {
		$language = $this->makeLanguage();
		$this->expectException(LanguageException::class);
		$language->updateFile('strings.txt', ['tag' => 'value']);
	}

	public function testUpdateFileWritesAndIsReadableBack(): void {
		$language = $this->makeLanguage();
		$filename = 'test_language_file_'.uniqid().'.php';

		$language->updateFile($filename, ['greeting' => 'Hello']);

		$expected_path = OPENMVC_ROOT.'/sites/default/language/en/'.$filename;
		$this->writtenFiles[] = $expected_path;

		$this->assertFileExists($expected_path);

		$_LANGUAGE = [];
		require $expected_path;
		$this->assertSame(['greeting' => 'Hello'], $_LANGUAGE);
	}

	public function testGetAbsoluteFilenameThrowsWhenFileDoesNotExistAnywhere(): void {
		$language = $this->makeLanguage();
		$this->expectException(LanguageException::class);
		$language->getAbsoluteFilename('definitely_does_not_exist_'.uniqid().'.php');
	}

	public function testGetAbsoluteFilenameFindsCoreDefaultFile(): void {
		$language = $this->makeLanguage();
		$filename = $language->getAbsoluteFilename('layout.php');
		$this->assertStringContainsString('core'.DIRECTORY_SEPARATOR.'language', $filename);
		$this->assertFileExists($filename);
	}
}
