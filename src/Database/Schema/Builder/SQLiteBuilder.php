<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Schema\Builder;

use Caldera\Database\DatabaseException;
use Caldera\Database\Schema\Builder\AbstractBuilder;
use Caldera\Database\Schema\Column;
use Caldera\Database\Schema\Key;
use Caldera\Database\Schema\Table;
use RuntimeException;

class SQLiteBuilder extends AbstractBuilder {

	/**
	 * Get tables
	 * @return array
	 */
	public function getTables(): array {
		$ret = [];
		$adapter = $this->database->getAdapter();
		$query = "SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%'";
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
		$query = "SELECT name FROM pragma_table_info(?) ORDER BY cid";
		$params = [
			$table
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
		$query = "SELECT name FROM sqlite_master WHERE type = 'index' WHERE tbl_name = ?";
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
		$query = "SELECT COUNT(*) AS total FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%' AND name = ?";
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
	 * @param  string  $table Table name
	 * @param  string  $column  Column name
	 * @return bool
	 */
	public function hasColumn(string $table, string $column): bool {
		$ret = false;
		$adapter = $this->database->getAdapter();
		$query = "SELECT name FROM pragma_table_info(?) WHERE name = ?";
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
	 * @param  string  $key  Key name
	 * @return bool
	 */
	public function hasKey(string $table, string $key): bool {
		$ret = false;
		$adapter = $this->database->getAdapter();
		$query = "SELECT COUNT(*) AS total FROM sqlite_master WHERE type = 'index' WHERE tbl_name = ? AND name = ?";
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
		$ret = false;
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
				if ( $key->getType() == Key::TYPE_PRIMARY ) continue;
				$key_defs[] = 'CREATE ' . $this->packKey($key, $name);
			}
		}
		$query = "CREATE TABLE `{$name}` (" . PHP_EOL;
		$query .= implode(',' . PHP_EOL, $column_defs);
		#
		$query .= PHP_EOL;
		$query .= ");";
		$adapter->begin();
		try {
			$ret = $adapter->query($query);
			#
			if ($key_defs) {
				foreach ($key_defs as $key_def) {
					$ret = $adapter->query($key_def);
					if (! $ret ) break;
				}
			}
			$adapter->commit();
		} catch (DatabaseException $e) {
			$adapter->rollback();
			throw $e;
		}
		#
		return $ret;
	}

	/**
	 * Drop a table
	 * @param  string $table Table name
	 * @return bool
	 */
	public function dropTable(string $table): bool {
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
		$ret = false;
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
						$column_defs[] = "ALTER TABLE `{$table}` ADD COLUMN " . $this->packColumn($column);
					break;
					case Column::OPERATION_DROP:
						$col_name = $column->getName();
						$column_defs[] = "ALTER TABLE `{$table}` DROP COLUMN `{$col_name}`";
					break;
					case Column::OPERATION_MODIFY:
						throw new RuntimeException('Column modification is not supported');
					break;
					case Column::OPERATION_RENAME:
						$col_name = $column->getName();
						$old_name = $column->getOldName();
						$column_defs[] = "ALTER TABLE `{$table}` RENAME COLUMN `{$old_name}` TO `{$col_name}`";
					break;
				}
			}
		}
		if ($keys) {
			foreach ($keys as $key) {
				switch ( $key->getOperation() ) {
					case Key::OPERATION_ADD:
						$key_defs[] = 'CREATE ' . $this->packKey($key, $table);
					break;
					case Key::OPERATION_DROP:
						$index = 'INDEX ';
						if ( $key->getType() == Key::TYPE_PRIMARY ) {
							throw new RuntimeException('Primary key deletion is not supported');
						}
						$key_defs[] = 'DROP INDEX `' . $key->getName() . '`';
					break;
				}
			}
		}
		#
		$adapter->begin();
		try {
			if ($column_defs) {
				foreach ($column_defs as $column_def) {
					$ret = $adapter->query($column_def);
					if (! $ret ) break;
				}
			}
			#
			if ($key_defs) {
				foreach ($key_defs as $key_def) {
					$ret = $adapter->query($key_def);
					if (! $ret ) break;
				}
			}
			#
			$adapter->commit();
		} catch (DatabaseException $e) {
			$adapter->rollback();
			throw $e;
		}
		#
		return $ret;
	}

	/**
	 * Rename a table
	 * @param  string $from Current table name
	 * @param  string $to   New table name
	 * @return bool
	 */
	public function renameTable(string $from, string $to) {
		$adapter = $this->database->getAdapter();
		$query = "ALTER TABLE `{$from}` RENAME TO `{$to}`;";
		return $adapter->query($query);
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
				$type = 'INTEGER';
			break;
			case Column::TYPE_BINARY:
				$type = 'BLOB';
			break;
			case Column::TYPE_BOOLEAN:
				$type = 'INTEGER';
			break;
			case Column::TYPE_CHAR:
				$type = 'TEXT';
			break;
			case Column::TYPE_DATE:
				$type = 'TEXT';
			break;
			case Column::TYPE_DATETIME:
				$type = 'TEXT';
			break;
			case Column::TYPE_DECIMAL:
				$type = 'REAL';
			break;
			case Column::TYPE_DOUBLE:
				$type = 'REAL';
			break;
			case Column::TYPE_ENUM:
				$type = 'TEXT';
			break;
			case Column::TYPE_FLOAT:
				$type = 'REAL';
			break;
			case Column::TYPE_INT:
				$type = 'INTEGER';
			break;
			case Column::TYPE_JSON:
				$type = 'TEXT';
			break;
			case Column::TYPE_LONGTEXT:
				$type = 'TEXT';
			break;
			case Column::TYPE_MEDIUMINT:
				$type = 'INTEGER';
			break;
			case Column::TYPE_MEDIUMTEXT:
				$type = 'TEXT';
			break;
			case Column::TYPE_SMALLINT:
				$type = 'INTEGER';
			break;
			case Column::TYPE_STRING:
				$type = 'TEXT';
			break;
			case Column::TYPE_TEXT:
				$type = 'TEXT';
			break;
			case Column::TYPE_TIME:
				$type = 'TEXT';
			break;
			case Column::TYPE_TIMESTAMP:
				$type = 'INTEGER';
			break;
			case Column::TYPE_TINYINT:
				$type = 'INTEGER';
			break;
			default:
				# Unknown type, just pass it as-is and hope for the best
				$type = $column->getType();
			break;
		}
		$ret = sprintf('`%s`', $column->getName());
		$ret .= sprintf(' %s', $type);
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
		#
		$ret .= $column->isNullable() ? ' NULL' : ' NOT NULL';
		#
		if ( $column->isAutoIncrement() ) {
			$ret .= sprintf(' PRIMARY KEY AUTOINCREMENT');
		}
		if ( $column->getDefault() !== null ) {
			$default = $column->getDefault();
			if ( is_object($default) ) {
				$ret .= sprintf(" DEFAULT %s", $default->value);
			} else {
				$ret .= sprintf(" DEFAULT '%s'", $default);
			}
		}
		return $ret;
	}

	/**
	 * Pack a Key object into a valid SQL string
	 * @param  Key    $key   Key object
	 * @param  string $table Table name
	 * @return string
	 */
	protected function packKey(Key $key, string $table): string {
		$type = '';
		switch ( $key->getType() ) {
			case Key::TYPE_INDEX:
				$type = 'INDEX';
			break;
			case Key::TYPE_PRIMARY:
				throw new RuntimeException('Primary key creation is not supported');
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
		$ret .= sprintf(' ON `%s`(%s)', $table, implode(', ', $columns));
		return $ret;
	}
}
