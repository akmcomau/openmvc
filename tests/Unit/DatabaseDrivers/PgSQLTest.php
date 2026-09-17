<?php

namespace tests\Unit\DatabaseDrivers;

use tests\FrameworkTestCase;
use tests\fixtures\models\Widget;
use core\classes\database_drivers\PgSQL;

class PgSQLTest extends FrameworkTestCase {

	protected function makeDriver(): PgSQL {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase('pgsql');
		$widget = new Widget($config, $database);
		return new PgSQL($config, $database, $widget);
	}

	public function testGetDataTypeAutoIncrementUsesSerial(): void {
		$driver = $this->makeDriver();
		$this->assertSame('SERIAL NOT NULL', $driver->getDataType(['data_type' => 'int', 'auto_increment' => TRUE]));
		$this->assertSame('SERIAL8 NOT NULL', $driver->getDataType(['data_type' => 'bigint', 'auto_increment' => TRUE]));
	}

	public function testGetDataTypeNonAutoIncrementInt(): void {
		$driver = $this->makeDriver();
		$this->assertSame('INT NOT NULL', $driver->getDataType(['data_type' => 'int']));
	}

	public function testGetDataTypeNullAllowedAddsExplicitNull(): void {
		$driver = $this->makeDriver();
		$this->assertSame('INT NULL', $driver->getDataType(['data_type' => 'int', 'null_allowed' => TRUE]));
	}

	public function testGetDataTypeNumericCombinesPrecisionAndScale(): void {
		$driver = $this->makeDriver();
		// PgSQL's numeric(precision, scale) sums [0]+[1] into position 0
		// unlike MySQL's decimal(precision, scale).
		$this->assertSame('NUMERIC(12,2) NOT NULL', $driver->getDataType(['data_type' => 'numeric', 'data_length' => [10, 2]]));
	}

	public function testGetDataTypeWithoutFullOmitsNullAndDefault(): void {
		$driver = $this->makeDriver();
		$this->assertSame('INT', $driver->getDataType(['data_type' => 'int', 'default_value' => '5'], FALSE));
	}

	public function testGetDataTypeSimpleTypes(): void {
		$driver = $this->makeDriver();
		$this->assertSame('UUID NOT NULL', $driver->getDataType(['data_type' => 'uuid']));
		$this->assertSame('REAL NOT NULL', $driver->getDataType(['data_type' => 'float']));
		$this->assertSame('DOUBLE PRECISION NOT NULL', $driver->getDataType(['data_type' => 'double']));
		$this->assertSame('TEXT NOT NULL', $driver->getDataType(['data_type' => 'text']));
		$this->assertSame('BYTEA NOT NULL', $driver->getDataType(['data_type' => 'blob']));
		$this->assertSame('INET NOT NULL', $driver->getDataType(['data_type' => 'inet']));
	}

	public function testGetDataTypeThrowsForInvalidType(): void {
		$driver = $this->makeDriver();
		$this->expectException(\core\classes\exceptions\ModelException::class);
		$driver->getDataType(['data_type' => 'not-a-real-type']);
	}

	public function testTranslateDataTypeRoundTrip(): void {
		$driver = $this->makeDriver();
		$this->assertSame('int', $driver->translateDataType('integer'));
		$this->assertSame('bigint', $driver->translateDataType('bigint'));
		$this->assertSame('float', $driver->translateDataType('real'));
		$this->assertSame('double', $driver->translateDataType('double precision'));
		$this->assertSame('datetime', $driver->translateDataType('timestamp with time zone'));
		$this->assertSame('bool', $driver->translateDataType('boolean'));
		$this->assertSame('blob', $driver->translateDataType('bytea'));
	}

	public function testTranslateDataTypeThrowsForUnknownPostgresType(): void {
		$driver = $this->makeDriver();
		$this->expectException(\ErrorException::class);
		$driver->translateDataType('not-a-real-pg-type');
	}

	public function testIndexConstraintNameJoinsColumns(): void {
		$driver = $this->makeDriver();
		$this->assertSame('widget_name', $driver->indexConstraintName('name'));
		$this->assertSame('widget_name_active', $driver->indexConstraintName(['name', 'active']));
	}

	public function testIndexConstraintNameStripsFunctionWrapper(): void {
		$driver = $this->makeDriver();
		// A column expression like "lower(name)" should resolve to just the
		// inner column list for the generated constraint name.
		$this->assertSame('widget_name', $driver->indexConstraintName(['lower(name)']));
	}

	public function testCreateTableGeneratesExpectedSql(): void {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase('pgsql');
		$widget = new Widget($config, $database);
		$driver = new PgSQL($config, $database, $widget);

		$database->expects($this->once())
			->method('executeQuery')
			->with($this->logicalAnd(
				$this->stringContains('CREATE TABLE widget'),
				$this->stringContains('PRIMARY KEY (widget_id)')
			), TRUE)
			->willReturn(NULL);

		$driver->createTable();
	}

	public function testAddIndexGeneratesExpectedSql(): void {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase('pgsql');
		$widget = new Widget($config, $database);
		$driver = new PgSQL($config, $database, $widget);

		$database->expects($this->once())
			->method('executeQuery')
			->with('CREATE INDEX widget_name_idx ON widget(name)', TRUE)
			->willReturn(NULL);

		$driver->addIndex('name');
	}
}
