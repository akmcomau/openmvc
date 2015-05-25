<?php

namespace core\classes\database_drivers;

use ReflectionClass;
use core\classes\DatabaseDriver;
use core\classes\exceptions\AutoloaderException;
use core\classes\exceptions\ModelException;

class MySQL extends DatabaseDriver {

	/**
	 * Create the  table this model is associated with
	 */
	public function createTable() {
		// create the table
		$sql = 'CREATE TABLE '.$this->table." (\n";

		// add the columns
		foreach ($this->columns as $column => $data) {
			$sql .= "\t$column ".$this->getDataType($data).",\n";
		}

		// add the indexes
		foreach ($this->indexes as $column) {
			if (is_array($column)) {
				$sql .= "\tINDEX (".join(',', $column)."),\n";
			}
			else {
				$sql .= "\tINDEX ($column),\n";
			}
		}

		// add the uniques
		foreach ($this->uniques as $column) {
			if (is_array($column)) {
				$sql .= "\tUNIQUE INDEX (".join(',', $column)."),\n";
			}
			else {
				$sql .= "\tUNIQUE INDEX ($column),\n";
			}
		}

		// add the primary key
		$sql .= "\tPRIMARY KEY (".$this->primary_key.")\n";

		// make an InnoDB type database
		$sql .= ") ENGINE=InnoDB DEFAULT CHARACTER SET = utf8;\n";

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
	 * Create the  table foreign keys this model is associated with
	 */
	public function createForeignKeys() {
		// add the forign keys
		$sql = '';
		foreach ($this->foreign_keys as $column => $foreign) {
			$foreign_table  = $foreign[0];
			$foreign_column = $foreign[1];
			$sql .= "ALTER TABLE ".$this->table." ADD CONSTRAINT FOREIGN KEY ($column) REFERENCES $foreign_table ($foreign_column);";
		}

		if ($sql != '') {
			return $this->database->executeQuery($sql);
		}

		return TRUE;
	}

	/**
	 * Translate an element of the $columns array to a  data type clause
	 * @param $data An element from $this->columns
	 * @return \b string An SQL fragment
	 * @throws ModelException If there is an invalid data_type
	 */
	public function getDataType($data) {
		$type = '';
		switch ($data['data_type']) {
			case 'smallint':
				$type = 'SMALLINT';
				break;

			case 'int':
				$type = 'INT';
				break;

			case 'bigint':
				$type = 'BIGINT';
				break;

			case 'numeric':
				$type = 'DECIMAL';
				if (isset($data['data_length']) && $data['data_length']) {
					$type.= '('.join(',', $data['data_length']).')';
				}
				break;

			case 'date':
				$type = 'DATE';
				break;

			case 'datetime':
				$type = 'DATETIME';
				break;

			case 'bool':
				$type = 'BOOL';
				break;

			case 'text':
				if (!isset($data['data_length'])) {
					$type = 'LONGTEXT';
				}
				elseif ((int)$data['data_length'] <= 128) {
					$type = 'CHAR('.(int)$data['data_length'].')';
				}
				elseif ((int)$data['data_length'] <= 256) {
					$type = 'VARCHAR('.(int)$data['data_length'].')';
				}
				elseif ((int)$data['data_length'] <= 65535) {
					$type = 'TEXT';
				}
				elseif ((int)$data['data_length'] <= 16777215) {
					$type = 'MEDIUMTEXT';
				}
				else {
					$type = 'LONGTEXT';
				}
				break;

			case 'blob':
				if (!isset($data['data_length'])) {
					$type = 'LONGBLOB';
				}
				elseif ((int)$data['data_length'] <= 256) {
					$type = 'TINYBLOB';
				}
				elseif ((int)$data['data_length'] <= 65535) {
					$type = 'BLOB';
				}
				elseif ((int)$data['data_length'] <= 16777215) {
					$type = 'MEDIUMBLOB';
				}
				else {
					$type = 'LONGBLOB';
				}
				break;

			default:
				throw new ModelException("Invalid data type: ".$data['data_type']);
				break;
		}

		if (!(isset($data['null_allowed']) && $data['null_allowed'])) {
			$type .= " NOT NULL";
		}

		if (isset($data['auto_increment']) && $data['auto_increment']) {
			$type .= " AUTO_INCREMENT";
		}

		if (isset($data['default_value'])) {
			$type .= " DEFAULT ".$data['default_value'];
		}

		return $type;
	}

	/**
	 * Get the schema of the table this model is associated with
	 */
	public function getTableSchemaMySQL() {
		if (is_null($this->table)) return;

		/** @TODO: Implement mysql database upating */
		throw new \ErrorException('TODO implement mysql database updating');
	}

	public function addColumn($name, $data) {
		throw new \ErrorException('TODO');
	}

	public function alterColumn($name, $data) {
		throw new \ErrorException('TODO');
	}

	public function dropColumn($name) {
		throw new \ErrorException('TODO');
	}

	public function addIndex($name, $columns) {
		throw new \ErrorException('TODO');
	}

	public function dropIndex($name) {
		throw new \ErrorException('TODO');
	}

	public function addUnique($name, $columns) {
		throw new \ErrorException('TODO');
	}

	public function dropUnique($name) {
		throw new \ErrorException('TODO');
	}

	public function addForeignKey($name, $data) {
		throw new \ErrorException('TODO');
	}

	public function dropForeignKey($name) {
		throw new \ErrorException('TODO');
	}

	protected function setTimezone($timezone) {
		throw new \ErrorException('TODO');
	}
}
