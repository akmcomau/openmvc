<?php

namespace core\classes\database_drivers;

use ReflectionClass;
use core\classes\DatabaseDriver;
use core\classes\exceptions\AutoloaderException;
use core\classes\exceptions\ModelException;

class PgSQL extends DatabaseDriver {

	/**
	 * Create the PostgreSQL table this model is associated with
	 */
	public function createTable() {
		// create the table
		$sql = 'CREATE TABLE '.$this->table." (\n";

		// add the columns
		foreach ($this->columns as $column => $data) {
			$sql .= "\t$column ".$this->getDataType($data).",\n";
		}

		// add the primary key
		$sql .= "\tPRIMARY KEY (".$this->primary_key.")\n";

		// make an InnoDB type database
		$sql .= ");\n";

		return $this->database->executeQuery($sql);
	}

	/**
	 * Drop the table this model is associated with
	 */
	public function dropTable() {
		if (is_null($this->table)) return;

		$sql = 'DROP TABLE '.$this->table;
		return $this->database->executeQuery($sql);
	}

	/**
	 * Create the PostgreSQL table foreign keys this model is associated with
	 */
	public function createForeignKeys() {
		// add the forign keys
		foreach ($this->foreign_keys as $column => $foreign) {
			$foreign_table  = $foreign[0];
			$foreign_column = $foreign[1];
			$this->addForeignKey($column, $foreign_table, $foreign_column);
		}
	}

	/**
	 * Create the PostgreSQL table indexes this model is associated with
	 */
	public function createIndexes() {
		// add the indexes
		foreach ($this->indexes as $column) {
			$this->addIndex($column);
		}
	}

	/**
	 * Create the PostgreSQL table unique constraints this model is associated with
	 */
	public function createUniques() {
		// add the indexes
		foreach ($this->uniques as $column) {
			$this->addUnique($column);
		}

		// add the partial indexes
		foreach ($this->partial_uniques as $columns) {
			$condition = array_shift($columns);
			$this->addPartialUnique($condition, $columns);
		}
	}

	/**
	 * Translate an element of the $columns array to a PostgreSQL data type clause
	 * @param $data An element from $this->columns
	 * @return \b string An SQL fragment
	 * @throws ModelException If there is an invalid data_type
	 */
	public function getDataType($data, $full = TRUE) {
		$type = '';
		switch ($data['data_type']) {
			case 'smallint':
				if (isset($data['auto_increment']) && $data['auto_increment']) {
					$type = 'SERIAL';
				}
				else {
					$type = 'SMALLINT';
				}
				break;

			case 'int':
				if (isset($data['auto_increment']) && $data['auto_increment']) {
					$type = 'SERIAL';
				}
				else {
					$type = 'INT';
				}
				break;

			case 'bigint':
				if (isset($data['auto_increment']) && $data['auto_increment']) {
					$type = 'SERIAL8';
				}
				else {
					$type = 'BIGINT';
				}
				break;

			case 'numeric':
				$type = 'NUMERIC';
				if (isset($data['data_length']) && $data['data_length']) {
					$data['data_length'][0] += $data['data_length'][1];
					$type.= '('.join(',', $data['data_length']).')';
				}
				break;

			case 'float':
				return 'real';
				break;

			case 'double':
				return 'double precision';
				break;

			case 'date':
				$type = 'DATE';
				break;

			case 'datetime':
				$type = 'TIMESTAMP WITH time zone';
				break;

			case 'bool':
				$type = 'BOOL';
				break;

			case 'text':
				$type = 'TEXT';
				break;

			case 'blob':
				$type = 'BYTEA';
				break;

			case 'inet':
				$type = 'INET';
				break;

			default:
				throw new ModelException("Invalid data type: ".$data['data_type']);
				break;
		}

		if ($full) {
			if (!(isset($data['null_allowed']) && $data['null_allowed'])) {
				$type .= " NOT NULL";
			}
			else {
				$type .= " NULL";
			}

			if (isset($data['default_value'])) {
				$type .= " DEFAULT ".$data['default_value'];
			}
		}

		return $type;
	}


	public function translateDataType($type) {
		switch($type) {
			case 'smallint':
				return 'smallint';
				break;

			case 'integer':
				return 'int';
				break;

			case 'bigint':
				return 'bigint';
				break;

			case 'numeric':
				return 'numeric';
				break;

			case 'date':
				return 'date';
				break;

			case 'real':
				return 'float';
				break;

			case 'double precision':
				return 'double';
				break;

			case 'timestamp with time zone':
				return 'datetime';
				break;

			case 'boolean':
				return 'bool';
				break;

			case 'text':
				return 'text';
				break;

			case 'inet':
				return 'inet';
				break;

			case 'bytea':
				return 'blob';
				break;
		}

		throw new \ErrorException("Unknown Postgres data type: $type");
	}

	/**
	 * Get the schema of the table this model is associated with
	 */
	public function getTableSchema() {
		if (is_null($this->table)) return;

		// does this table exist
		$sql = "SELECT relname FROM pg_class WHERE relname = ".$this->database->quote($this->table)." AND relkind='r'";
		$table = $this->database->queryMulti($sql);
		if (count($table) == 0) {
			return NULL;
		}

		// get the columns
		$sql = "
			SELECT *,
				column_name,
				data_type,
				is_nullable,
				column_default
			FROM information_schema.columns
			WHERE
				table_schema   = 'public'
				AND table_name = ".$this->database->quote($this->table)."
		";
		$columns = [];
		$columns_raw = $this->database->queryMulti($sql);
		foreach ($columns_raw as $column) {
			// is this an autoincrement field
			$auto_increment = FALSE;
			if (preg_match('/^nextval/', $column['column_default'])) {
				$column['column_default'] = NULL;
				$auto_increment = TRUE;
			}

			$columns[$column['column_name']] = [
				'data_type'      => $this->translateDataType($column['data_type']),
				'null_allowed'   => ($column['is_nullable'] == 'YES') ? TRUE : FALSE,
				'auto_increment' => $auto_increment,
				'default_value'  => $column['column_default'],
			];
		}

		// get the indexes
		$sql = "
			SELECT
				i.relname as indname,
				i.relowner as indowner,
				idx.indrelid::regclass,
				am.amname as indam,
				idx.indkey,
				ARRAY(
					SELECT pg_get_indexdef(idx.indexrelid, k + 1, true)
					FROM generate_subscripts(idx.indkey, 1) as k
					ORDER BY k
				) as indkey_names,
				idx.indexprs IS NOT NULL as indexprs,
				idx.indpred IS NOT NULL as indpred
			FROM
				pg_index as idx
				JOIN pg_class as i ON i.oid = idx.indexrelid
				JOIN pg_am as am ON i.relam = am.oid
				JOIN pg_namespace as ns ON ns.oid = i.relnamespace AND ns.nspname = ANY(current_schemas(false))
		";
		$indexes = [];
		$raw_indexes = $this->database->queryMulti($sql);
		foreach ($raw_indexes as $index) {
			if ($index['indrelid'] == $this->table) {
				preg_match('/^{(.*)}$/', $index['indkey_names'], $matches);
				$array = str_getcsv($matches[1]);
				$indexes[$index['indname']] = $array;
			}
		}

		// get the foreign keys
		$sql = "
			SELECT *,
				tc.constraint_name,
				kcu.column_name,
				constraint_type,
				ccu.table_name  AS foreign_table_name,
				ccu.column_name AS foreign_column_name
			FROM
				information_schema.table_constraints AS tc
				JOIN information_schema.key_column_usage AS kcu
					ON tc.constraint_name = kcu.constraint_name
				JOIN information_schema.constraint_column_usage AS ccu
					ON ccu.constraint_name = tc.constraint_name
			WHERE
				tc.table_name=".$this->database->quote($this->table)."
		";
		$primary_key = NULL;
		$foreign_keys = [];
		$uniques = [];
		$raw_constraints = $this->database->queryMulti($sql);
		foreach ($raw_constraints as $constraint) {
			if ($constraint['constraint_type'] == 'FOREIGN KEY') {
				$foreign_keys[$constraint['column_name']] = [
					$constraint['foreign_table_name'],
					$constraint['foreign_column_name'],
				];
			}
			elseif ($constraint['constraint_type'] == 'PRIMARY KEY') {
				$primary_key = [
					'name'   => $constraint['constraint_name'],
					'column' => $constraint['column_name']
				];
			}
			elseif ($constraint['constraint_type'] == 'UNIQUE') {
				if (isset($indexes[$constraint['constraint_name']])) {
					$uniques[$constraint['constraint_name']] = $indexes[$constraint['constraint_name']];
					unset($indexes[$constraint['constraint_name']]);
				}
			}
		}

		$schema = [
			'primary_key'  => $primary_key,
			'columns'      => $columns,
			'indexes'      => $indexes,
			'uniques'      => $uniques,
			'foreign_keys' => $foreign_keys,
		];

		return $schema;
	}

	public function addColumn($name, $data) {
		$sql = "ALTER TABLE ".$this->table." ADD COLUMN $name ".$this->getDataType($data);
		$this->database->executeQuery($sql);
	}

	public function alterColumn($name, $data) {
		$sql = "ALTER TABLE ".$this->table." ALTER COLUMN $name TYPE ".$this->getDataType($data, FALSE);
		$this->database->executeQuery($sql);

		$sql = "ALTER TABLE ".$this->table." ALTER COLUMN $name";
		if (isset($data['default_value'])) {
			$sql .= " SET DEFAULT ".$data['default_value'];
		}
		else {
			$sql .= " DROP DEFAULT";
		}
		$this->database->executeQuery($sql);

		$sql = "ALTER TABLE ".$this->table." ALTER COLUMN $name";
		if (!(isset($data['null_allowed']) && $data['null_allowed'])) {
			$sql .= " SET NOT NULL";

			if (isset($data['default_value'])) {
				$sql_inner = "UPDATE ".$this->table." SET $name = ".$data['default_value'];
				$this->database->executeQuery($sql_inner);
			}
		}
		else {
			$sql .= " DROP NOT NULL";
		}
		$this->database->executeQuery($sql);
	}

	public function dropColumn($name) {
		$sql = "ALTER TABLE ".$this->table." DROP COLUMN $name";
		$this->database->executeQuery($sql);
	}

	public function addIndex($columns) {
		$sql = "CREATE INDEX ".$this->indexConstraintName($columns)."_idx ON ".$this->table."(".$this->indexConstraintColumns($columns).")";
		$this->database->executeQuery($sql);
	}

	public function dropIndex($columns) {
		$sql = "DROP INDEX ".$this->indexConstraintName($columns)."_idx";
		$this->database->executeQuery($sql);
	}

	public function addUnique($columns) {
		$sql = "ALTER TABLE ONLY ".$this->table." ADD CONSTRAINT ".$this->indexConstraintName($columns)."_key UNIQUE (".$this->indexConstraintColumns($columns).")";
		$this->database->executeQuery($sql);
	}

	public function addPartialUnique($condition, $columns) {
		$sql = "CREATE  UNIQUE INDEX ".$this->indexConstraintName($columns)."_part_uni ON ".$this->table." (".$this->indexConstraintColumns($columns).") WHERE $condition";
		$this->database->executeQuery($sql);
	}

	public function dropUnique($name) {
		if (is_array($name)) {
			$name = $this->indexConstraintName($columns)."_key";
		}
		$sql = "ALTER TABLE ONLY ".$this->table." DROP CONSTRAINT ".$name;
		$this->database->executeQuery($sql);
	}

	public function addForeignKey($column, $foreign_table, $foreign_column) {
		$sql = "ALTER TABLE ONLY ".$this->table." ADD CONSTRAINT ".$this->indexConstraintName($column)."_fk FOREIGN KEY ($column) REFERENCES $foreign_table ($foreign_column)";
		$this->database->executeQuery($sql);
	}

	public function dropForeignKey($column) {
		$sql = "ALTER TABLE ONLY ".$this->table." DROP CONSTRAINT ".$this->indexConstraintName($column)."_fk";
		$this->database->executeQuery($sql);
	}

	protected function indexConstraintName($columns) {
		if (!is_array($columns)) {
			$columns = [$columns];
		}
		foreach ($columns as &$column) {
			if (preg_match('/^.*\((.*)\)$/', $column, $matches)) {
				$column = $matches[1];
			}
		}
		return $this->table."_".join('_', $columns);
	}

	protected function indexConstraintColumns($columns) {
		if (is_array($columns)) {
			return join(',', $columns);
		}
		else {
			return $columns;
		}
	}

	public function setTimezone($timezone) {
		$timezone = $this->database->quote($timezone);
		$sql = "SET timezone TO $timezone";
		$this->database->executeQuery($sql);
	}

	public function begin() {
		$this->database->executeQuery('BEGIN;');
	}

	public function commit() {
		$this->database->executeQuery('COMMIT;');
	}

	public function rollback() {
		$this->database->executeQuery('ROLLBACK;');
	}
}
