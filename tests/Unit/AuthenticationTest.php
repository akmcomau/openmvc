<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Authentication;
use core\classes\models\Customer;
use core\classes\models\Administrator;
use core\classes\exceptions\AuthenticationException;

class AuthenticationTest extends FrameworkTestCase {

	protected function tearDown(): void {
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_destroy();
		}
		$_SESSION = [];
		parent::tearDown();
	}

	protected function makeAuthentication(): Authentication {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase();
		$request = $this->makeRequest($config, [], [], [], $database);
		return new Authentication($config, $database, $request);
	}

	protected function makeCustomer(int $id): Customer {
		$customer = new Customer($this->makeConfig(), $this->makeMockDatabase());
		$customer->id = $id;
		$customer->login = 'test-user';
		return $customer;
	}

	protected function makeAdministrator(int $id): Administrator {
		$admin = new Administrator($this->makeConfig(), $this->makeMockDatabase());
		$admin->id = $id;
		$admin->first_name = 'Ada';
		$admin->last_name = 'Min';
		return $admin;
	}

	// -- not logged in by default --

	public function testNotLoggedInByDefault(): void {
		$auth = $this->makeAuthentication();
		$this->assertFalse($auth->loggedIn());
		$this->assertFalse($auth->customerLoggedIn());
		$this->assertFalse($auth->administratorLoggedIn());
		$this->assertNull($auth->getCustomerID());
		$this->assertNull($auth->getAdministratorID());
	}

	// -- loginCustomer() --

	public function testLoginCustomerThrowsWithNoId(): void {
		$auth = $this->makeAuthentication();
		$customer = new Customer($this->makeConfig(), $this->makeMockDatabase());

		$this->expectException(AuthenticationException::class);
		$auth->loginCustomer($customer);
	}

	public function testLoginCustomerSetsLoggedInState(): void {
		$auth = $this->makeAuthentication();
		$customer = $this->makeCustomer(42);

		$auth->loginCustomer($customer);

		$this->assertTrue($auth->loggedIn());
		$this->assertSame(42, $auth->getCustomerID());
		$this->assertNotFalse($auth->customerLoggedIn());
	}

	public function testLoginCustomerPersistsToSession(): void {
		$auth = $this->makeAuthentication();
		$customer = $this->makeCustomer(42);

		$auth->loginCustomer($customer);

		$this->assertSame(42, $_SESSION['authentication']['customer']['customer_id']);
	}

	public function testLoginCustomerRegeneratesSessionId(): void {
		// Regression test for the session-fixation fix: login must rotate
		// the session id, so a pre-authentication session id (which an
		// attacker could have fixed/learned) is useless after login.
		session_start();
		$idBeforeLogin = session_id();

		$auth = $this->makeAuthentication();
		$auth->loginCustomer($this->makeCustomer(42));

		$this->assertNotSame($idBeforeLogin, session_id());
	}

	public function testLoginAdministratorRegeneratesSessionId(): void {
		session_start();
		$idBeforeLogin = session_id();

		$auth = $this->makeAuthentication();
		$auth->loginAdministrator($this->makeAdministrator(7));

		$this->assertNotSame($idBeforeLogin, session_id());
	}

	public function testLoginCustomerClearsToken(): void {
		$auth = $this->makeAuthentication();
		$customer = $this->makeCustomer(42);
		$customer->token = 'some-reset-token';

		$auth->loginCustomer($customer);

		$this->assertNull($customer->token);
	}

	public function testLoginCustomerAddsLoginEvent(): void {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase();
		$request = $this->makeRequest($config, [], [], [], $database);
		$auth = new Authentication($config, $database, $request);

		$auth->loginCustomer($this->makeCustomer(42));

		$events = array_filter($request->getEvents(), fn($e) => $e['name'] === 'Customer Login');
		$this->assertNotEmpty($events);
	}

	// -- loginAdministrator() --

	public function testLoginAdministratorThrowsWithNoId(): void {
		$auth = $this->makeAuthentication();
		$admin = new Administrator($this->makeConfig(), $this->makeMockDatabase());

		$this->expectException(AuthenticationException::class);
		$auth->loginAdministrator($admin);
	}

	public function testLoginAdministratorSetsLoggedInState(): void {
		$auth = $this->makeAuthentication();
		$auth->loginAdministrator($this->makeAdministrator(7));

		$this->assertTrue($auth->loggedIn());
		$this->assertSame(7, $auth->getAdministratorID());
	}

	public function testAdministratorLoggedInIncludesFullName(): void {
		$auth = $this->makeAuthentication();
		$auth->loginAdministrator($this->makeAdministrator(7));

		$data = $auth->administratorLoggedIn();
		$this->assertSame('Ada Min', $data['administrator_name']);
	}

	// -- logout --

	public function testLogoutCustomerClearsState(): void {
		$auth = $this->makeAuthentication();
		$auth->loginCustomer($this->makeCustomer(42));

		$auth->logoutCustomer();

		$this->assertFalse($auth->customerLoggedIn());
		$this->assertNull($auth->getCustomerID());
	}

	public function testLogoutAdministratorClearsState(): void {
		$auth = $this->makeAuthentication();
		$auth->loginAdministrator($this->makeAdministrator(7));

		$auth->logoutAdministrator();

		$this->assertFalse($auth->administratorLoggedIn());
		$this->assertNull($auth->getAdministratorID());
	}

	public function testLogoutClearsBothCustomerAndAdministrator(): void {
		$auth = $this->makeAuthentication();
		$auth->loginCustomer($this->makeCustomer(42));
		$auth->loginAdministrator($this->makeAdministrator(7));

		$auth->logout();

		$this->assertFalse($auth->loggedIn());
	}

	public function testLoggingInANewCustomerLogsOutThePreviousOne(): void {
		$auth = $this->makeAuthentication();
		$auth->loginCustomer($this->makeCustomer(42));

		$auth->loginCustomer($this->makeCustomer(99));

		$this->assertSame(99, $auth->getCustomerID());
	}

	// -- session state survives across a fresh Authentication instance
	// (as would happen on the next request) --

	public function testLoggedInStateIsReadFromSessionOnConstruction(): void {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase();
		$request = $this->makeRequest($config, [], [], [], $database);
		$auth = new Authentication($config, $database, $request);
		$auth->loginCustomer($this->makeCustomer(42));

		// A fresh Authentication instance (same underlying $_SESSION, as on
		// a subsequent request) should pick up the logged-in state.
		$request2 = $this->makeRequest($config, [], [], [], $database);
		$auth2 = new Authentication($config, $database, $request2);

		$this->assertTrue($auth2->customerLoggedIn() !== FALSE);
		$this->assertSame(42, $auth2->getCustomerID());
	}

	// -- force password change flag --

	public function testForcePasswordChangeDefaultsToDisabled(): void {
		$auth = $this->makeAuthentication();
		$this->assertFalse($auth->forcePasswordChangeEnabled());
	}

	public function testForcePasswordChangeCanBeEnabledAndDisabled(): void {
		$auth = $this->makeAuthentication();

		$auth->forcePasswordChange(TRUE);
		$this->assertTrue($auth->forcePasswordChangeEnabled());

		$auth->forcePasswordChange(FALSE);
		$this->assertFalse($auth->forcePasswordChangeEnabled());
	}
}
