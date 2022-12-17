<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Schema;

use Closure;
use RuntimeException;

use Caldera\Database\Database;
use Caldera\Database\Adapter\MySQLAdapter;
use Caldera\Database\Adapter\SQLiteAdapter;
use Caldera\Database\Schema\Builder\BuilderInterface;
use Caldera\Database\Schema\Builder\MySQLBuilder;
use Caldera\Database\Schema\Builder\SQLiteBuilder;
use Caldera\Database\Schema\Table;

class Schema {

	/**
	 * Database instance
	 * @var Database
	 */
	protected $database;

	/**
	 * Builder instance
	 * @var BuilderInterface
	 */
	protected $builder;

	/**
	 * Constructor
	 * @param Database $database  Database instance
	 */
	public function __construct(Database $database) {
		$this->database = $database;
		# Now get a builder instance
		$adapter = $this->database->getAdapter();
		# Check for subclasses
		if ( $adapter instanceof MySQLAdapter ) {
			$this->builder = new MySQLBuilder($this->database);
		} else if ( $adapter instanceof SQLiteAdapter ) {
			$this->builder = new SQLiteBuilder($this->database);
		} else {
			throw new RuntimeException( sprintf( "Unsupported adapter '%s'", get_class($adapter) ) );
		}
	}

	/**
	 * Get the tables on the current database
	 * @return array
	 */
	public function getTables(): array {
		return $this->builder->getTables();
	}

	/**
	 * Check if a table exists or not in the current database
	 * @param  string  $name Table name
	 * @return bool
	 */
	public function hasTable(string $name): bool {
		return $this->builder->hasTable($name);
	}

	/**
	 * Check if the given table has a specific column
	 * @param  string  $table Table name
	 * @param  string  $name  Column name
	 * @return bool
	 */
	public function hasColumn(string $table, string $name): bool {
		return $this->builder->hasColumn($table, $name);
	}

	/**
	 * Check if the given table has a specific key
	 * @param  string  $table Table name
	 * @param  string  $name  Key name
	 * @return bool
	 */
	public function hasKey(string $table, string $name): bool {
		return $this->builder->hasKey($table, $name);
	}

	/**
	 * Get table columns
	 * @param  string $table Table name
	 * @return array
	 */
	public function getColumns(string $table): array {
		return $this->builder->getColumns($table);
	}

	/**
	 * Get table keys
	 * @param  string $table Table name
	 * @return array
	 */
	public function getKeys(string $table): array {
		return $this->builder->getKeys($table);
	}

	/**
	 * Create a table
	 * @param  string  $name     Table name
	 * @param  Closure $callback Callback for building the table
	 * @return mixed
	 */
	public function create(string $name, Closure $callback) {
		$table = new Table($name);
		call_user_func($callback, $table);
		return $this->builder->createTable($table);
	}

	/**
	 * Create a table after asserting that it DOES NOT exist
	 * @param  string  $name     Table name
	 * @param  Closure $callback Callback for building the table
	 * @return mixed
	 */
	public function createIfNotExists(string $name, Closure $callback) {
		$ret = false;
		if (! $this->hasTable($name) ) {
			$ret = $this->create($name, $callback);
		}
		return $ret;
	}

	/**
	 * Delete a table
	 * @param  string $name Table name
	 * @return mixed
	 */
	public function drop(string $name) {
		return $this->builder->dropTable($name);
	}

	/**
	 * Delete a table after asserting that it DOES exist
	 * @param  string $name Table name
	 * @return mixed
	 */
	public function dropIfExists(string $name) {
		$ret = false;
		if ( $this->hasTable($name) ) {
			$ret = $this->drop($name);
		}
		return $ret;
	}

	/**
	 * Modify a table
	 * @param  string  $name     Table name
	 * @param  Closure $callback Callback for modifying the table
	 * @return mixed
	 */
	public function table(string $name, Closure $callback) {
		$ret = false;
		if ( $this->hasTable($name) ) {
			$table = new Table($name);
			call_user_func($callback, $table);
			$ret = $this->builder->alterTable($table);
		}
		return $ret;
	}

	/**
	 * Rename a table
	 * @param  string $from From name
	 * @param  string $to   To name
	 * @return mixed
	 */
	public function rename(string $from, string $to) {
		$ret = false;
		if ( $this->hasTable($from) ) {
			$ret = $this->builder->renameTable($from, $to);
		}
		return $ret;
	}
}
