<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\exceptions\RedirectException;

class RequestTest extends FrameworkTestCase {

	// -- regression tests for the `if ($value)` -> `if (!is_null($value))` fix:
	// falsy-but-meaningful values (0, '', FALSE) must actually be settable. --

	public function testGetParamCanBeSetToZero(): void {
		$request = $this->makeRequest($this->makeConfig());
		$request->getParam('qty', 0);
		$this->assertSame(0, $request->getParam('qty'));
	}

	public function testGetParamCanBeSetToEmptyString(): void {
		$request = $this->makeRequest($this->makeConfig());
		$request->getParam('name', '');
		$this->assertSame('', $request->getParam('name'));
	}

	public function testGetParamCanBeSetToFalse(): void {
		$request = $this->makeRequest($this->makeConfig());
		$request->getParam('flag', FALSE);
		$this->assertFalse($request->getParam('flag'));
	}

	public function testGetParamReadsExistingSuperglobalValue(): void {
		$request = $this->makeRequest($this->makeConfig(), ['existing' => 'from-get']);
		$this->assertSame('from-get', $request->getParam('existing'));
	}

	public function testGetParamReturnsNullWhenUnset(): void {
		$request = $this->makeRequest($this->makeConfig());
		$this->assertNull($request->getParam('missing'));
	}

	public function testPostParamCanBeSetToZero(): void {
		$request = $this->makeRequest($this->makeConfig());
		$request->postParam('qty', 0);
		$this->assertSame(0, $request->postParam('qty'));
	}

	public function testRequestParamCanBeSetToZero(): void {
		$request = $this->makeRequest($this->makeConfig());
		$request->requestParam('qty', 0);
		$this->assertSame(0, $request->requestParam('qty'));
	}

	public function testFileParamCanBeSetToFalse(): void {
		$request = $this->makeRequest($this->makeConfig());
		$request->fileParam('upload', FALSE);
		$this->assertFalse($request->fileParam('upload'));
	}

	// -- serverParam / events --

	public function testServerParamReadsAndDefaultsToNull(): void {
		$request = $this->makeRequest($this->makeConfig(), [], [], ['CUSTOM_HEADER' => 'value']);
		$this->assertSame('value', $request->serverParam('CUSTOM_HEADER'));
		$this->assertNull($request->serverParam('DOES_NOT_EXIST'));
	}

	public function testAddEventAndGetEvents(): void {
		$request = $this->makeRequest($this->makeConfig());
		$request->addEvent('Login', 5, 1.5, 'text');

		$this->assertSame([[
			'name' => 'Login',
			'int' => 5,
			'double' => 1.5,
			'text' => 'text',
		]], $request->getEvents());
	}

	// -- controller/method/params plumbing --

	public function testSetAndGetControllerAndMethod(): void {
		$request = $this->makeRequest($this->makeConfig());
		$request->setControllerClass('\core\controllers\Root');
		$request->setMethodName('index');
		$request->setMethodParams(['a', 'b']);

		$this->assertSame('\core\controllers\Root', $request->getControllerClass());
		$this->assertSame('index', $request->getMethodName());
		$this->assertSame(['a', 'b'], $request->getMethodParams());
	}

	public function testVerifyMethodParamsThrowsRedirectOnMismatch(): void {
		$config = $this->makeConfig();
		$request = $this->makeRequest($config);
		$request->setControllerClass('\core\controllers\Root');
		$request->setMethodName('index');
		$request->setMethodParams(['old']);

		$this->expectException(RedirectException::class);
		$request->verifyMethodParams(['new']);
	}

	public function testVerifyMethodParamsPassesWhenParamsMatch(): void {
		$config = $this->makeConfig();
		$request = $this->makeRequest($config);
		$request->setControllerClass('\core\controllers\Root');
		$request->setMethodName('index');
		$request->setMethodParams(['same']);

		// No exception should be thrown.
		$request->verifyMethodParams(['same']);
		$this->assertSame(['same'], $request->getMethodParams());
	}

	public function testClearDispatcherParamsRemovesDispatcherKeysOnly(): void {
		$request = $this->makeRequest($this->makeConfig(), [
			'method' => 'x',
			'controller' => 'y',
			'params' => 'z',
			'keep_me' => 'yes',
		]);

		$request->clearDispatcherParams();

		$this->assertNull($request->getParam('method'));
		$this->assertNull($request->getParam('controller'));
		$this->assertNull($request->getParam('params'));
		$this->assertSame('yes', $request->getParam('keep_me'));
	}

	public function testGetDatabaseIsNullBeforeSetDatabase(): void {
		$request = $this->makeRequest($this->makeConfig());
		$this->assertNull($request->getDatabase());
	}
}
