<?php

namespace tests\Unit\DatabaseDrivers;

use tests\FrameworkTestCase;
use tests\fixtures\models\Widget;
use core\classes\database_drivers\MySQL;

class MySQLTest extends FrameworkTestCase {

	protected function makeDriver(): MySQL {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase('mysql');
		$widget = new Widget($config, $database);
		return new MySQL($config, $database, $widget);
	}

	public function testGetDataTypeInt(): void {
		$driver = $this->makeDriver();
		$this->assertSame('INT NOT NULL', $driver->getDataType(['data_type' => 'int']));
	}

	public function testGetDataTypeNullAllowedOmitsNotNull(): void {
		$driver = $this->makeDriver();
		$this->assertSame('INT', $driver->getDataType(['data_type' => 'int', 'null_allowed' => TRUE]));
	}

	public function testGetDataTypeAutoIncrement(): void {
		$driver = $this->makeDriver();
		$this->assertSame('BIGINT NOT NULL AUTO_INCREMENT', $driver->getDataType(['data_type' => 'bigint', 'auto_increment' => TRUE]));
	}

	public function testGetDataTypeWithDefaultValue(): void {
		$driver = $this->makeDriver();
		$this->assertSame("BOOL NOT NULL DEFAULT TRUE", $driver->getDataType(['data_type' => 'bool', 'default_value' => 'TRUE']));
	}

	public function testGetDataTypeNumericWithLength(): void {
		$driver = $this->makeDriver();
		$this->assertSame('DECIMAL(10,2) NOT NULL', $driver->getDataType(['data_type' => 'numeric', 'data_length' => [10, 2]]));
	}

	public function testGetDataTypeTextTierSizing(): void {
		$driver = $this->makeDriver();
		$this->assertSame('LONGTEXT NOT NULL', $driver->getDataType(['data_type' => 'text']));
		$this->assertSame('CHAR(64) NOT NULL', $driver->getDataType(['data_type' => 'text', 'data_length' => 64]));
		$this->assertSame('VARCHAR(200) NOT NULL', $driver->getDataType(['data_type' => 'text', 'data_length' => 200]));
		$this->assertSame('TEXT NOT NULL', $driver->getDataType(['data_type' => 'text', 'data_length' => 1000]));
		$this->assertSame('MEDIUMTEXT NOT NULL', $driver->getDataType(['data_type' => 'text', 'data_length' => 100000]));
		$this->assertSame('LONGTEXT NOT NULL', $driver->getDataType(['data_type' => 'text', 'data_length' => 100000000]));
	}

	public function testGetDataTypeBlobTierSizing(): void {
		$driver = $this->makeDriver();
		$this->assertSame('LONGBLOB NOT NULL', $driver->getDataType(['data_type' => 'blob']));
		$this->assertSame('TINYBLOB NOT NULL', $driver->getDataType(['data_type' => 'blob', 'data_length' => 100]));
		$this->assertSame('BLOB NOT NULL', $driver->getDataType(['data_type' => 'blob', 'data_length' => 1000]));
		$this->assertSame('MEDIUMBLOB NOT NULL', $driver->getDataType(['data_type' => 'blob', 'data_length' => 100000]));
	}

	public function testGetDataTypeThrowsForInvalidType(): void {
		$driver = $this->makeDriver();
		$this->expectException(\core\classes\exceptions\ModelException::class);
		$driver->getDataType(['data_type' => 'not-a-real-type']);
	}

	public function testCreateTableGeneratesExpectedSql(): void {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase('mysql');
		$widget = new Widget($config, $database);
		$driver = new MySQL($config, $database, $widget);

		$database->expects($this->once())
			->method('executeQuery')
			->with($this->stringContains('CREATE TABLE widget'))
			->willReturn(NULL);

		$driver->createTable();
	}

	public function testDropTableGeneratesExpectedSql(): void {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase('mysql');
		$widget = new Widget($config, $database);
		$driver = new MySQL($config, $database, $widget);

		$database->expects($this->once())
			->method('executeQuery')
			->with('DROP TABLE widget')
			->willReturn(NULL);

		$driver->dropTable();
	}
}
