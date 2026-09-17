<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use tests\fixtures\models\Widget;

class ModelTest extends FrameworkTestCase {

	protected function makeWidget(string $engine = 'pgsql') {
		$config = $this->makeConfig();
		$database = $this->makeMockDatabase($engine);
		return new Widget($config, $database);
	}

	// -- quote() --

	public function testQuoteDelegatesScalarValuesToDatabase(): void {
		$widget = $this->makeWidget();
		$this->assertSame("'normal'", $widget->quote('normal'));
	}

	public function testQuoteOfResourceProducesPostgresHexLiteral(): void {
		$widget = $this->makeWidget();

		$resource = fopen('php://memory', 'r+');
		fwrite($resource, 'hello');
		rewind($resource);

		$result = $widget->quote($resource);

		$expected = "E'\\\\x".bin2hex('hello')."'";
		$this->assertSame($expected, $result);
	}

	public function testQuoteNoLongerBypassesEscapingForPostgresEscapeLookingStrings(): void {
		// Regression test for the removed Model::quote() bypass: a plain
		// string that merely starts with the same bytes as the resource
		// branch's own output (E'\...) must still be escaped via the
		// database driver, never returned verbatim - that bypass was a
		// SQL injection (an attacker-controlled value starting with those
		// bytes skipped quoting entirely).
		$widget = $this->makeWidget('pgsql');

		$malicious = "E'\\\\'); DROP TABLE widget; --";
		$result = $widget->quote($malicious);

		$this->assertSame("'".addslashes($malicious)."'", $result);
		$this->assertNotSame($malicious, $result);
	}

	// -- generateWhereClause(): simple equality --

	public function testGenerateWhereClauseSimpleEquality(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['name' => 'Foo']);
		$this->assertSame("widget.widget_name='Foo'", $sql);
	}

	public function testGenerateWhereClauseWithNullValue(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['name' => NULL]);
		$this->assertSame('widget.widget_name IS NULL', $sql);
	}

	public function testGenerateWhereClauseAndsMultipleColumns(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['name' => 'Foo', 'active' => TRUE]);
		$this->assertSame("widget.widget_name='Foo' AND widget.widget_active=TRUE", $sql);
	}

	public function testGenerateWhereClauseSilentlyDropsUnknownColumns(): void {
		// Columns that resolve to neither a real column nor a relationship
		// where_field are skipped rather than injected into the SQL string.
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['not_a_real_column' => 'x']);
		$this->assertSame('', $sql);
	}

	public function testGenerateWhereClauseResolvesRelationshipColumn(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['category_name' => 'Tools']);
		$this->assertSame("widget_category.category_name='Tools'", $sql);
	}

	// -- generateWhereClause(): operator formats --

	public function testGenerateWhereClauseLikeOperator(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['name' => ['type' => 'like', 'value' => '%foo%']]);
		$this->assertSame("widget.widget_name LIKE '%foo%'", $sql);
	}

	public function testGenerateWhereClauseInOperator(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['id' => ['type' => 'in', 'value' => [1, 2, 3]]]);
		$this->assertSame('widget.widget_id IN (1,2,3)', $sql);
	}

	public function testGenerateWhereClauseInOperatorWithEmptyArrayMatchesNothing(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['id' => ['type' => 'in', 'value' => []]]);
		$this->assertSame('1 = 2', $sql);
	}

	public function testGenerateWhereClauseNotInOperator(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['id' => ['type' => 'notin', 'value' => [1, 2]]]);
		$this->assertSame('widget.widget_id NOT IN (1,2)', $sql);
	}

	public function testGenerateWhereClauseIsNullOperator(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['name' => ['type' => 'isnull']]);
		$this->assertSame('widget.widget_name IS NULL', $sql);
	}

	public function testGenerateWhereClauseIsNotNullOperator(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['name' => ['type' => 'isnotnull']]);
		$this->assertSame('widget.widget_name IS NOT NULL', $sql);
	}

	public function testGenerateWhereClauseComparisonOperators(): void {
		$widget = $this->makeWidget();
		$this->assertSame('widget.widget_id>5', $widget->generateWhereClause(['id' => ['type' => '>', 'value' => 5]]));
		$this->assertSame('widget.widget_id>=5', $widget->generateWhereClause(['id' => ['type' => '>=', 'value' => 5]]));
		$this->assertSame('widget.widget_id<5', $widget->generateWhereClause(['id' => ['type' => '<', 'value' => 5]]));
		$this->assertSame('widget.widget_id<=5', $widget->generateWhereClause(['id' => ['type' => '<=', 'value' => 5]]));
		$this->assertSame('widget.widget_id!=5', $widget->generateWhereClause(['id' => ['type' => '!=', 'value' => 5]]));
	}

	public function testGenerateWhereClauseUpperAndLowerOperators(): void {
		$widget = $this->makeWidget();
		$this->assertSame("UPPER(widget.widget_name)='FOO'", $widget->generateWhereClause(['name' => ['type' => 'upper=', 'value' => 'foo']]));
		$this->assertSame("LOWER(widget.widget_name)='foo'", $widget->generateWhereClause(['name' => ['type' => 'lower=', 'value' => 'FOO']]));
	}

	public function testGenerateWhereClauseOrGroupsWithParentheses(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['or' => ['name' => 'Foo', 'active' => TRUE]]);
		$this->assertSame("(widget.widget_name='Foo' OR widget.widget_active=TRUE)", $sql);
	}

	public function testGenerateWhereClauseAndGroupWithParentheses(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['and' => ['name' => 'Foo', 'active' => TRUE]]);
		$this->assertSame("(widget.widget_name='Foo' AND widget.widget_active=TRUE)", $sql);
	}

	public function testGenerateWhereClauseSqlEscapeHatchIsPassedThroughVerbatim(): void {
		// Documents (rather than endorses) the 'SQL' raw-passthrough
		// escape hatch: callers must only ever pass a fixed, trusted SQL
		// fragment through it, never request/user-supplied data.
		$widget = $this->makeWidget();
		$sql = $widget->generateWhereClause(['SQL' => 'widget.widget_id > 5']);
		$this->assertSame('widget.widget_id > 5', $sql);
	}

	// -- getColumnName() --

	public function testGetColumnNameResolvesUnprefixedName(): void {
		$widget = $this->makeWidget();
		$this->assertSame('widget_name', $widget->getColumnName('name'));
	}

	public function testGetColumnNameResolvesFullyQualifiedName(): void {
		$widget = $this->makeWidget();
		$this->assertSame('widget_name', $widget->getColumnName('widget_name'));
	}

	public function testGetColumnNameResolvesRelationshipField(): void {
		$widget = $this->makeWidget();
		$this->assertSame('category_name', $widget->getColumnName('category_name'));
	}

	public function testGetColumnNameReturnsNullForUnknownColumn(): void {
		$widget = $this->makeWidget();
		$this->assertNull($widget->getColumnName('not_a_column'));
	}

	// -- getOrderGroupSQL(): GROUP BY injection regression --

	public function testGetOrderGroupSqlOnlyIncludesAllowlistedGroupByColumns(): void {
		// Regression test: getOrderGroupSQL() must validate every GROUP BY
		// column through getColumnName() and drop anything that doesn't
		// resolve, exactly like it already does for ORDER BY - previously
		// the whole $grouping array was concatenated into the SQL string
		// unescaped.
		$widget = $this->makeWidget();
		$sql = $widget->getOrderGroupSQL(NULL, NULL, ['widget_name', "widget_id); DROP TABLE widget; --"]);

		$this->assertSame(' GROUP BY widget_name', $sql);
		$this->assertStringNotContainsString('DROP TABLE', $sql);
	}

	public function testGetOrderGroupSqlOmitsGroupByClauseWhenNoColumnsResolve(): void {
		$widget = $this->makeWidget();
		$sql = $widget->getOrderGroupSQL(NULL, NULL, ['not_a_column']);
		$this->assertSame('', $sql);
	}

	// -- getOrderGroupSQL(): ORDER BY --

	public function testGetOrderGroupSqlOnlyIncludesAllowlistedOrderByColumns(): void {
		$widget = $this->makeWidget();
		$sql = $widget->getOrderGroupSQL(['widget_name' => 'asc', 'not_a_column' => 'desc']);
		$this->assertSame(' ORDER BY widget_name ASC', $sql);
	}

	public function testGetOrderGroupSqlDefaultsInvalidDirectionToDesc(): void {
		$widget = $this->makeWidget();
		$sql = $widget->getOrderGroupSQL(['widget_name' => 'not-a-real-direction']);
		$this->assertSame(' ORDER BY widget_name DESC', $sql);
	}

	public function testGetOrderGroupSqlRandomOrderingUsesEnginesRandomFunction(): void {
		$mysqlWidget = $this->makeWidget('mysql');
		$this->assertSame(' ORDER BY RAND()', $mysqlWidget->getOrderGroupSQL(['random()' => 'asc']));

		$pgsqlWidget = $this->makeWidget('pgsql');
		$this->assertSame(' ORDER BY RANDOM()', $pgsqlWidget->getOrderGroupSQL(['random()' => 'asc']));
	}

	// -- getOrderGroupSQL(): pagination --

	public function testGetOrderGroupSqlLimitOnly(): void {
		$widget = $this->makeWidget();
		$sql = $widget->getOrderGroupSQL(NULL, ['limit' => 5]);
		$this->assertSame(' LIMIT 5', $sql);
	}

	public function testGetOrderGroupSqlLimitAndOffset(): void {
		$widget = $this->makeWidget();
		$sql = $widget->getOrderGroupSQL(NULL, ['limit' => 10, 'offset' => 20]);
		$this->assertSame(' OFFSET 20 LIMIT 10', $sql);
	}

	public function testGetOrderGroupSqlCastsLimitAndOffsetToInteger(): void {
		// Regression-style check that non-numeric pagination input can't
		// smuggle SQL through LIMIT/OFFSET - both are (int)-cast.
		$widget = $this->makeWidget();
		$sql = $widget->getOrderGroupSQL(NULL, ['limit' => '10; DROP TABLE widget', 'offset' => '0 OR 1=1']);
		$this->assertSame(' OFFSET 0 LIMIT 10', $sql);
	}

	// -- generateFromClause() --

	public function testGenerateFromClauseIncludesOwnTableOnly(): void {
		$widget = $this->makeWidget();
		$this->assertSame('widget', $widget->generateFromClause([]));
	}

	public function testGenerateFromClauseAddsJoinWhenRelationshipFieldReferenced(): void {
		$widget = $this->makeWidget();
		$sql = $widget->generateFromClause(['category_name' => 'Tools']);
		$this->assertSame('widget JOIN widget_category ON widget_category.id = widget.category_id', $sql);
	}

	public function testGenerateFromClauseDoesNotDuplicateJoinsAlreadyPresent(): void {
		$widget = $this->makeWidget();
		$in_from = ['widget' => 1, 'widget_category' => 1];
		$sql = $widget->generateFromClause(['category_name' => 'Tools'], NULL, $in_from);
		$this->assertSame('', $sql);
	}
}
