<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Schema\Builder;

use Caldera\Database\Schema\Builder\AbstractBuilder;
use Caldera\Database\Schema\Column;
use Caldera\Database\Schema\Table;
use Caldera\Database\Schema\Key;

class MySQLBuilder extends AbstractBuilder {

	/**
	 * Get tables
	 * @return array
	 */
	public function getTables(): array {
		$ret = [];
		$adapter = $this->database->getAdapter();
		$query = "SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = DATABASE()";
		$tables = $adapter->query($query);
		if ($tables) {
			foreach ($tables as $table) {
				$ret[] = $table->name;
			}
		}
		return $ret;
	}

	/**
	 * Get table columns
	 * @param  string $table Table name
	 * @return array
	 */
	public function getColumns(string $table): array {
		$ret = [];
		$adapter = $this->database->getAdapter();
		$query = "SELECT COLUMN_NAME AS name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE() ORDER BY ORDINAL_POSITION";
		$params = [
			$table,
		];
		$columns = $adapter->query($query, $params);
		if ($columns) {
			foreach ($columns as $column) {
				$ret[] = $column->name;
			}
		}
		return $ret;
	}

	/**
	 * Get table keys
	 * @param  string $table Table name
	 * @return array
	 */
	public function getKeys(string $table): array {
		$ret = [];
		$adapter = $this->database->getAdapter();
		$query = "SELECT DISTINCT INDEX_NAME AS name FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()";
		$params = [
			$table,
		];
		$keys = $adapter->query($query, $params);
		if ($keys) {
			foreach ($keys as $key) {
				$ret[] = $key->name;
			}
		}
		return $ret;
	}

	/**
	 * Check if a table exists
	 * @param  string  $table Table name
	 * @return bool
	 */
	public function hasTable(string $table): bool {
		$ret = false;
		$adapter = $this->database->getAdapter();
		$query = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()";
		$params = [
			$table,
		];
		$result = $adapter->query($query, $params, function($stmt) {
			return $stmt->fetch();
		});
		if ($result) {
			$ret = $result->total > 0;
		}
		return $ret;
	}

	/**
	 * Check if a column exists in the specified table
	 * @param  string  $table  Table name
	 * @param  string  $column Column name
	 * @return bool
	 */
	public function hasColumn(string $table, string $column): bool {
		$ret = false;
		$adapter = $this->database->getAdapter();
		$query = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?";
		$params = [
			$table,
			$column
		];
		$result = $adapter->query($query, $params, function($stmt) {
			return $stmt->fetch();
		});
		if ($result) {
			$ret = $result->total > 0;
		}
		return $ret;
	}

	/**
	 * Check if a key exists in the specified table
	 * @param  string  $table Table name
	 * @param  string  $key   Key name
	 * @return bool
	 */
	public function hasKey(string $table, string $key): bool {
		$ret = false;
		$adapter = $this->database->getAdapter();
		$query = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE() AND INDEX_NAME = ?";
		$params = [
			$table,
			$key
		];
		$result = $adapter->query($query, $params, function($stmt) {
			return $stmt->fetch();
		});
		if ($result) {
			$ret = $result->total > 0;
		}
		return $ret;
	}

	/**
	 * Create a table
	 * @param  Table $table Table object
	 * @return bool
	 */
	public function createTable(Table $table): bool {
		$adapter = $this->database->getAdapter();
		$columns = $table->getColumns();
		$keys = $table->getKeys();
		$name = $table->getName();
		#
		$column_defs = [];
		$key_defs = [];
		#
		if ($columns) {
			foreach ($columns as $column) {
				$column_defs[] = '    ' . $this->packColumn($column);
			}
		}
		if ($keys) {
			foreach ($keys as $key) {
				$key_defs[] = '    ' . $this->packKey($key);
			}
		}
		$query = "CREATE TABLE `{$name}` (" . PHP_EOL;
		$query .= implode(',' . PHP_EOL, $column_defs);
		if ($key_defs) {
			$query .= ',' . PHP_EOL;
			$query .= implode(',' . PHP_EOL, $key_defs);
		}
		$query .= PHP_EOL;
		$query .= ");";
		return $adapter->query($query);
	}

	/**
	 * Drop a table
	 * @param  string $table Table name
	 * @return bool
	 */
	public function dropTable(string $table) {
		$adapter = $this->database->getAdapter();
		$query = "DROP TABLE `{$table}`;";
		return $adapter->query($query);
	}

	/**
	 * Modify a table
	 * @param  Table $table Table object
	 * @return bool
	 */
	public function alterTable(Table $table): bool {
		$adapter = $this->database->getAdapter();
		$columns = $table->getColumns();
		$keys = $table->getKeys();
		$table = $table->getName();
		#
		$column_defs = [];
		$key_defs = [];
		#
		if ($columns) {
			foreach ($columns as $column) {
				switch ( $column->getOperation() ) {
					case Column::OPERATION_ADD:
						$after = $column->getAfter();
						$column_defs[] = '    ADD ' . $this->packColumn($column) . ($after ? " AFTER `{$after}`" : '');
					break;
					case Column::OPERATION_DROP:
						$col_name = $column->getName();
						$column_defs[] = '    DROP COLUMN ' . "`{$col_name}`";
					break;
					case Column::OPERATION_MODIFY:
						$col_name = $column->getName();
						$column_defs[] = '    CHANGE COLUMN ' . "`{$col_name}` " . $this->packColumn($column);
					break;
					case Column::OPERATION_RENAME:
						$col_name = $column->getName();
						$old_name = $column->getOldName();
						$attributes = $this->getColumnAttributes($table, $old_name);
						$column->nullable($attributes->nullable);
						$column->default($attributes->default);
						$packed = $this->packColumn($column);
						$column_defs[] = '    CHANGE COLUMN ' . "`{$old_name}` " . str_replace('{TYPE}', $attributes->type, $packed);
					break;
				}
			}
		}
		if ($keys) {
			foreach ($keys as $key) {
				switch ( $key->getOperation() ) {
					case Key::OPERATION_ADD:
						$key_defs[] = '    ADD ' . $this->packKey($key);
					break;
					case Key::OPERATION_DROP:
						$index = 'INDEX ';
						if ( $key->getType() == Key::TYPE_PRIMARY ) {
							$constraint = 'PRIMARY KEY ';
						}
						$key_defs[] = '    DROP ' . $index . '`' . $key->getName() . '`';
					break;
				}
			}
		}
		$query = "ALTER TABLE `{$table}`" . PHP_EOL;
		if ($column_defs) {
			$query .= implode(',' . PHP_EOL, $column_defs);
			if ($key_defs) {
				$query .= ',' . PHP_EOL;
			}
		}
		if ($key_defs) {
			$query .= implode(',' . PHP_EOL, $key_defs);
		}
		$query .= ';';
		return $adapter->query($query);
	}

	/**
	 * Rename a table
	 * @param  string $from Current table name
	 * @param  string $to   New table name
	 * @return bool
	 */
	public function renameTable(string $from, string $to) {
		$adapter = $this->database->getAdapter();
		$query = "RENAME TABLE `{$from}` TO `{$to}`;";
		return $adapter->query($query);
	}

	/**
	 * Get the attributes of the given column
	 * @param  string $table  Table name
	 * @param  string $column Column name
	 * @return mixed
	 */
	protected function getColumnAttributes(string $table, string $column) {
		$ret = null;
		$adapter = $this->database->getAdapter();
		$query = "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?";
		$params = [
			$table,
			$column
		];
		$result = $adapter->query($query, $params, function($stmt) {
			return $stmt->fetch();
		});
		if ($result) {
			$ret = (object) [
				'type' => $result->COLUMN_TYPE,
				'nullable' => $result->IS_NULLABLE != 'NO',
				'default' => $result->COLUMN_DEFAULT
			];
		}
		return $ret;
	}

	/**
	 * Pack a Column object into a valid SQL string
	 * @param  Column $column Column object
	 * @return string
	 */
	protected function packColumn(Column $column): string {
		$type = '';
		switch ( $column->getType() ) {
			case Column::TYPE_BIGINT:
				$type = 'BIGINT';
			break;
			case Column::TYPE_BINARY:
				$type = 'BLOB';
			break;
			case Column::TYPE_BOOLEAN:
				$type = 'TINYINT';
			break;
			case Column::TYPE_CHAR:
				$type = 'CHAR';
			break;
			case Column::TYPE_DATE:
				$type = 'DATE';
			break;
			case Column::TYPE_DATETIME:
				$type = 'DATETIME';
			break;
			case Column::TYPE_DECIMAL:
				$type = 'DECIMAL';
			break;
			case Column::TYPE_DOUBLE:
				$type = 'DOUBLE';
			break;
			case Column::TYPE_ENUM:
				$type = 'ENUM';
			break;
			case Column::TYPE_FLOAT:
				$type = 'FLOAT';
			break;
			case Column::TYPE_INT:
				$type = 'INT';
			break;
			case Column::TYPE_JSON:
				$type = 'JSON';
			break;
			case Column::TYPE_LONGTEXT:
				$type = 'LONGTEXT';
			break;
			case Column::TYPE_MEDIUMINT:
				$type = 'MEDIUMINT';
			break;
			case Column::TYPE_MEDIUMTEXT:
				$type = 'MEDIUMTEXT';
			break;
			case Column::TYPE_SMALLINT:
				$type = 'SMALLINT';
			break;
			case Column::TYPE_STRING:
				$type = 'VARCHAR';
			break;
			case Column::TYPE_TEXT:
				$type = 'TEXT';
			break;
			case Column::TYPE_TIME:
				$type = 'TIME';
			break;
			case Column::TYPE_TIMESTAMP:
				$type = 'TIMESTAMP';
			break;
			case Column::TYPE_TINYINT:
				$type = 'TINYINT';
			break;
			default:
				# Unknown type, just pass it as-is and hope for the best
				$type = $column->getType();
			break;
		}
		$ret = sprintf('`%s`', $column->getName());
		$ret .= sprintf(' %s', $type);
		if ( $column->getLength() ) {
			$ret .= sprintf('(%d)', $column->getLength());
		}
		if ( $column->getPrecision() ) {
			$precision =$column->getPrecision();
			if ( is_array( $column->getPrecision() ) ) {
				$precision = implode(', ', $precision);
			}
			$ret .= sprintf('(%s)', $precision);
		}
		if ( $column->getOptions() ) {
			$options = array_map(function($option) {
				return sprintf("'%s'", $option);
			}, $column->getOptions());
			$ret .= sprintf('(%s)', implode(', ', $options));
		}
		if ( $column->isUnsigned() ) {
			$ret .= sprintf(' UNSIGNED');
		}
		#
		$ret .= $column->isNullable() ? ' NULL' : ' NOT NULL';
		#
		if ( $column->isAutoIncrement() ) {
			$ret .= sprintf(' AUTO_INCREMENT');
		}
		if ( $column->getDefault() !== null ) {
			$default = $column->getDefault();
			if ( is_object($default) && isset($default->value) ) {
				$ret .= sprintf(" DEFAULT %s", $default->value);
			} else {
				$ret .= sprintf(" DEFAULT '%s'", $default);
			}
		}
		return $ret;
	}

	/**
	 * Pack a Key object into a valid SQL string
	 * @param  Key $key Key object
	 * @return string
	 */
	protected function packKey(Key $key): string {
		$type = '';
		switch ( $key->getType() ) {
			case Key::TYPE_INDEX:
				$type = 'INDEX';
			break;
			case Key::TYPE_PRIMARY:
				$type = 'PRIMARY KEY';
			break;
			case Key::TYPE_UNIQUE:
				$type = 'UNIQUE INDEX';
			break;
			case Key::TYPE_FOREIGN:
				$type = 'FOREIGN KEY';
			break;
			default:
				# Unknown type, just pass it as-is and hope for the best
				$type = $key->getType();
			break;
		}
		$columns = array_map(function($column) {
			return sprintf('`%s`', $column);
		}, $key->getColumns());
		$ret = $type;
		$ret .= sprintf(' `%s`', $key->getName());
		$ret .= sprintf(' (%s)', implode(', ', $columns));
		return $ret;
	}
}
