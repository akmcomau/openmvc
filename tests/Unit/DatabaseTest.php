<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Database;
use ReflectionClass;

class DatabaseTest extends FrameworkTestCase {

	public function testEngineNoneNeverConnects(): void {
		$config = $this->makeConfig();
		$database = new Database($config);

		$this->assertSame('none', $database->getEngine());
		$this->assertNull($database->executeQuery('SELECT 1'));
		$this->assertNull($database->executeQuery('SELECT 1', TRUE));
		$this->assertSame('', $database->quote('anything'));
	}

	public function testQuoteCoercesBooleansIntegersAndNull(): void {
		$config = $this->makeConfig();
		// engine must not be 'none' to reach the type-coercion branches,
		// but we never actually connect - PDO::quote()/parent::quote() is
		// only reached for the plain-string case, which these tests avoid.
		$database = $this->makeDatabaseDouble('pgsql');

		$this->assertSame('TRUE', $database->quote(TRUE));
		$this->assertSame('FALSE', $database->quote(FALSE));
		// quote()'s declared ": string" return type coerces the (int)
		// cast in its integer branch to a numeric string.
		$this->assertSame('5', $database->quote(5));
		$this->assertSame('NULL', $database->quote(NULL));
	}

	/**
	 * A Database instance with its constructor bypassed (so no real PDO
	 * connection is ever attempted) and the given engine set via
	 * reflection, for exercising logic that would otherwise require a real
	 * database connection.
	 */
	protected function makeDatabaseDouble(string $engine, bool $is_masterdb = FALSE, ?Database $masterdb = NULL): Database {
		$reflection = new ReflectionClass(Database::class);
		$database = $reflection->newInstanceWithoutConstructor();

		$this->setProtectedProperty($database, 'engine', $engine);
		$this->setProtectedProperty($database, 'is_masterdb', $is_masterdb);
		if ($masterdb) {
			$this->setProtectedProperty($database, 'masterdb', $masterdb);
		}
		$this->setProtectedProperty($database, 'logger', \core\classes\Logger::getLogger('test'));

		return $database;
	}

	protected function setProtectedProperty(object $object, string $property, $value): void {
		$reflection = new ReflectionClass($object);
		$prop = $reflection->getProperty($property);
		$prop->setAccessible(true);
		$prop->setValue($object, $value);
	}

	public function testGetMasterDbReturnsSelfWhenAlreadyMaster(): void {
		$database = $this->makeDatabaseDouble('pgsql', TRUE);
		$this->assertSame($database, $database->getMasterDB());
	}

	public function testGetMasterDbReturnsInjectedMasterConnection(): void {
		$master = $this->makeDatabaseDouble('pgsql', TRUE);
		$slave = $this->makeDatabaseDouble('pgsql', FALSE, $master);

		$this->assertSame($master, $slave->getMasterDB());
	}

	// -- regression tests for the $use_masterdb routing fix: previously
	// executeQuery() accepted $use_masterdb but never used it, so writes
	// issued against a slave connection silently ran on the slave. --

	public function testExecuteQueryWithoutMasterFlagRunsOnCurrentConnection(): void {
		$database = $this->getMockBuilder(Database::class)
			->disableOriginalConstructor()
			->onlyMethods(['query'])
			->getMock();
		$this->setProtectedProperty($database, 'engine', 'pgsql');
		$this->setProtectedProperty($database, 'is_masterdb', FALSE);
		$this->setProtectedProperty($database, 'logger', \core\classes\Logger::getLogger('test'));

		$statement = $this->createMock(\PDOStatement::class);
		$database->expects($this->once())->method('query')->with('SELECT 1')->willReturn($statement);

		$this->assertSame($statement, $database->executeQuery('SELECT 1', FALSE));
	}

	public function testExecuteQueryWithMasterFlagRoutesToMasterConnectionWhenNotAlreadyMaster(): void {
		$master = $this->getMockBuilder(Database::class)
			->disableOriginalConstructor()
			->onlyMethods(['query'])
			->getMock();
		$this->setProtectedProperty($master, 'engine', 'pgsql');
		$this->setProtectedProperty($master, 'is_masterdb', TRUE);
		$this->setProtectedProperty($master, 'logger', \core\classes\Logger::getLogger('test'));

		$statement = $this->createMock(\PDOStatement::class);
		$master->expects($this->once())->method('query')->with('UPDATE widget SET x=1')->willReturn($statement);

		$slave = $this->getMockBuilder(Database::class)
			->disableOriginalConstructor()
			->onlyMethods(['query'])
			->getMock();
		$slave->expects($this->never())->method('query');
		$this->setProtectedProperty($slave, 'engine', 'pgsql');
		$this->setProtectedProperty($slave, 'is_masterdb', FALSE);
		$this->setProtectedProperty($slave, 'masterdb', $master);
		$this->setProtectedProperty($slave, 'logger', \core\classes\Logger::getLogger('test'));

		$result = $slave->executeQuery('UPDATE widget SET x=1', TRUE);

		$this->assertSame($statement, $result);
	}

	public function testExecuteQueryWithMasterFlagRunsDirectlyWhenAlreadyMaster(): void {
		$database = $this->getMockBuilder(Database::class)
			->disableOriginalConstructor()
			->onlyMethods(['query'])
			->getMock();
		$this->setProtectedProperty($database, 'engine', 'pgsql');
		$this->setProtectedProperty($database, 'is_masterdb', TRUE);
		$this->setProtectedProperty($database, 'logger', \core\classes\Logger::getLogger('test'));

		$statement = $this->createMock(\PDOStatement::class);
		$database->expects($this->once())->method('query')->with('UPDATE widget SET x=1')->willReturn($statement);

		$database->executeQuery('UPDATE widget SET x=1', TRUE);
	}

	public function testExecuteQueryThrowsDatabaseExceptionOnQueryFailure(): void {
		$database = $this->getMockBuilder(Database::class)
			->disableOriginalConstructor()
			->onlyMethods(['query', 'errorCode', 'errorInfo'])
			->getMock();
		$this->setProtectedProperty($database, 'engine', 'pgsql');
		$this->setProtectedProperty($database, 'is_masterdb', TRUE);
		$this->setProtectedProperty($database, 'logger', \core\classes\Logger::getLogger('test'));

		$database->method('query')->willReturn(FALSE);
		$database->method('errorCode')->willReturn('42000');
		$database->method('errorInfo')->willReturn(['42000', 1, 'syntax error']);

		$this->expectException(\core\classes\exceptions\DatabaseException::class);
		$database->executeQuery('NOT VALID SQL');
	}
}
