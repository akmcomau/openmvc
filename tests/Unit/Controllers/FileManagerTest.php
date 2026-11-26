<?php

namespace tests\Unit\Controllers;

use tests\FrameworkTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use core\controllers\administrator\FileManager;

/**
 * Tests for the file manager's sub path handling, which guards the folder
 * browser against directory traversal.
 */
class FileManagerTest extends FrameworkTestCase {

	protected function makeFileManager(): FileManager {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase();
		$request = $this->makeRequest($config, [], [], [], $database);
		return new FileManager($config, $database, $request, new \core\classes\Response());
	}

	public static function rootSubPathProvider(): array {
		// A request with no sub_path at all is the plain "open the file
		// manager" page - it must not be treated as a rejected path.
		return [
			'missing'      => [NULL],
			'empty string' => [''],
		];
	}

	#[DataProvider('rootSubPathProvider')]
	public function testNoSubPathIsTheFolderRoot(?string $subPath): void {
		$this->assertSame('', $this->callProtectedMethod($this->makeFileManager(), 'sanitizeSubPath', [$subPath]));
	}

	public function testAnOrdinarySubPathIsKept(): void {
		$this->assertSame('/images/logos', $this->callProtectedMethod($this->makeFileManager(), 'sanitizeSubPath', ['/images/logos']));
	}

	public static function traversalProvider(): array {
		return [
			'parent'            => ['..'],
			'nested parent'     => ['/images/../../config'],
			'backslash parent'  => ['images\\..\\..\\config'],
		];
	}

	#[DataProvider('traversalProvider')]
	public function testTraversalIsRejected(string $subPath): void {
		$this->assertNull($this->callProtectedMethod($this->makeFileManager(), 'sanitizeSubPath', [$subPath]));
	}
}
