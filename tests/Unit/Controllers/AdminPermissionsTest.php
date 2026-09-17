<?php

namespace tests\Unit\Controllers;

use tests\FrameworkTestCase;
use ReflectionClass;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every core\controllers\administrator\* controller is meant to be
 * entirely admin-gated - core\classes\Dispatcher only enforces auth for a
 * method if it's present in Controller::getPermissions() (see
 * "if (isset($controller->getPermissions()[$method_name]))" in
 * Dispatcher::dispatchRequest()), so any public method missing from that
 * array is served with NO authentication check at all.
 *
 * A systematic sweep across these controllers (prompted by writing a
 * similar test for the satvue site) found four more instances of this bug
 * beyond the ones already known from the satvue review - the worst being
 * Customers::login($customer_id), a "log in as this customer" impersonation
 * feature (see the admin customer-list template's "Login as Customer"
 * button) that anyone, unauthenticated, could call directly to take over
 * any customer account. All four are fixed; this test locks that in and
 * covers every other admin controller so this class of regression is
 * caught immediately in the future.
 */
class AdminPermissionsTest extends FrameworkTestCase {

	public static function adminControllerProvider(): array {
		return [
			['core\controllers\administrator\Administrators'],
			['core\controllers\administrator\BlockCategories'],
			['core\controllers\administrator\Blocks'],
			['core\controllers\administrator\CategoryManager'],
			['core\controllers\administrator\Customers'],
			['core\controllers\administrator\FileManager'],
			['core\controllers\administrator\LanguageEditor'],
			['core\controllers\administrator\Modules'],
			['core\controllers\administrator\PageCategories'],
			['core\controllers\administrator\Pages'],
		];
	}

	#[DataProvider('adminControllerProvider')]
	public function testEveryPublicMethodHasAPermissionEntry(string $class): void {
		// Controller::__construct() has a lightweight "meta data only" path
		// when $request is omitted, which is enough to read getPermissions().
		$controller = new $class($this->makeConfig());
		$permissions = $controller->getPermissions();

		$reflection = new ReflectionClass($class);
		$missing = [];
		foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			if ($method->isStatic() || $method->isAbstract()) continue;
			if ($method->getDeclaringClass()->getName() !== $class) continue; // only methods declared directly on this controller
			if (str_starts_with($method->getName(), '__')) continue;

			if (!isset($permissions[$method->getName()])) {
				$missing[] = $method->getName();
			}
		}

		$this->assertSame([], $missing, "$class has public method(s) with no \$permissions entry, which the Dispatcher will serve with no authentication at all: ".implode(', ', $missing));
	}

	// -- explicit regression tests for the four confirmed, exploitable bugs --

	public function testCustomersLoginRequiresAdministratorPermission(): void {
		// The most severe of the four: unauthenticated "login as any
		// customer" account takeover.
		$controller = new \core\controllers\administrator\Customers($this->makeConfig());
		$this->assertSame(['administrator'], $controller->getPermissions()['login'] ?? NULL);
	}

	public function testCustomersCsvRequiresAdministratorPermission(): void {
		$controller = new \core\controllers\administrator\Customers($this->makeConfig());
		$this->assertSame(['administrator'], $controller->getPermissions()['csv'] ?? NULL);
	}

	public function testAdministratorsDeleteRequiresAdministratorPermission(): void {
		// Unauthenticated deletion of administrator accounts.
		$controller = new \core\controllers\administrator\Administrators($this->makeConfig());
		$this->assertSame(['administrator'], $controller->getPermissions()['delete'] ?? NULL);
	}

	public function testLanguageEditorEditRequiresAdministratorPermission(): void {
		// Unauthenticated site language/content string modification.
		$controller = new \core\controllers\administrator\LanguageEditor($this->makeConfig());
		$this->assertSame(['administrator'], $controller->getPermissions()['edit'] ?? NULL);
	}

	public function testModulesUninstallRequiresAdministratorPermission(): void {
		// Unauthenticated module removal.
		$controller = new \core\controllers\administrator\Modules($this->makeConfig());
		$this->assertSame(['administrator'], $controller->getPermissions()['uninstall'] ?? NULL);
	}
}
