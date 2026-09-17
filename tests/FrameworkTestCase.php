<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use core\classes\Config;
use core\classes\Database;
use core\classes\Request;

/**
 * Common fixtures for framework unit tests: a Config bound to the
 * tests/fixtures/config.php fixture site, a Request wired up from
 * caller-supplied superglobal values, and a Database double whose
 * quote()/getEngine()/executeQuery() behaviour is fully controlled (so SQL
 * building logic can be asserted on without a real database connection).
 */
abstract class FrameworkTestCase extends TestCase {

	const TEST_DOMAIN = 'test.local';

	protected function setUp(): void {
		parent::setUp();

		// Never let one test's request state leak into the next.
		$_GET = [];
		$_POST = [];
		$_REQUEST = [];
		$_FILES = [];
		$_COOKIE = [];
	}

	protected function makeConfig(string $domain = self::TEST_DOMAIN): Config {
		$config = new Config();
		$config->setSiteDomain($domain);
		return $config;
	}

	protected function makeRequest(Config $config, array $get = [], array $post = [], array $server = [], ?Database $database = NULL): Request {
		$_GET     = $get;
		$_POST    = $post;
		$_REQUEST = array_merge($get, $post);
		foreach ($server as $key => $value) {
			$_SERVER[$key] = $value;
		}

		$request = new Request($config);
		// Mimic what the Dispatcher always does before a controller method
		// runs in production - several classes (Pagination, URL) need a
		// controller/method set to build URLs without warnings.
		$request->setControllerClass('\core\controllers\Root');
		$request->setMethodName('index');
		$request->setMethodParams([]);

		// Controller::__construct() passes $request->getAuthentication()
		// into Layout's non-nullable Authentication parameter, so any test
		// that constructs a real Controller needs this called first (even
		// with nobody logged in) or it fatals with a TypeError.
		if ($database) {
			$request->setDatabase($database);
		}

		return $request;
	}

	/**
	 * Build a controller with a Database double already wired into its
	 * Request (see makeRequest()'s $database param), so the base
	 * Controller constructor's Layout/Authentication setup doesn't fatal.
	 */
	protected function makeController(string $class, Config $config, Database $database, Request $request) {
		return new $class($config, $database, $request, new \core\classes\Response());
	}

	protected function setProtectedProperty(object $object, string $property, $value): void {
		$reflection = new \ReflectionClass($object);
		$prop = $reflection->getProperty($property);
		$prop->setAccessible(true);
		$prop->setValue($object, $value);
	}

	protected function getProtectedProperty(object $object, string $property) {
		$reflection = new \ReflectionClass($object);
		$prop = $reflection->getProperty($property);
		$prop->setAccessible(true);
		return $prop->getValue($object);
	}

	protected function callProtectedMethod(object $object, string $method, array $args = []) {
		$reflection = new \ReflectionClass($object);
		$m = $reflection->getMethod($method);
		$m->setAccessible(true);
		return $m->invokeArgs($object, $args);
	}

	/**
	 * A Database double with a deterministic, real-looking quote()
	 * implementation (single-quoted, backslash-escaped) instead of the
	 * always-empty-string behaviour Database::quote() has for the real
	 * 'none' engine - useful for asserting on generated SQL fragments.
	 */
	protected function makeMockDatabase(string $engine = 'pgsql'): Database {
		$database = $this->getMockBuilder(Database::class)
			->disableOriginalConstructor()
			->onlyMethods(['quote', 'getEngine', 'executeQuery', 'queryValue', 'querySingle', 'queryMulti'])
			->getMock();

		$database->method('getEngine')->willReturn($engine);
		$database->method('quote')->willReturnCallback(function ($value) {
			if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
			if (is_int($value)) return (string)$value;
			if (is_null($value)) return 'NULL';
			return "'".addslashes((string)$value)."'";
		});

		return $database;
	}
}
