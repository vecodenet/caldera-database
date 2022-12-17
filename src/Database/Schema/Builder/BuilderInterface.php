<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Schema\Builder;

use Caldera\Database\Schema\Table;

interface BuilderInterface {

	/**
	 * Get tables
	 * @return array
	 */
	public function getTables(): array;

	/**
	 * Get table columns
	 * @param  string $table Table name
	 * @return array
	 */
	public function getColumns(string $table): array;

	/**
	 * Get table keys
	 * @param  string $table Table name
	 * @return array
	 */
	public function getKeys(string $table): array;

	/**
	 * Check if a table exists
	 * @param  string  $table Table name
	 * @return bool
	 */
	public function hasTable(string $table): bool;

	/**
	 * Check if a column exists in the specified table
	 * @param  string  $table Table name
	 * @param  string  $column  Column name
	 * @return bool
	 */
	public function hasColumn(string $table, string $column): bool;

	/**
	 * Check if a key exists in the specified table
	 * @param  string  $table Table name
	 * @param  string  $key  Key name
	 * @return bool
	 */
	public function hasKey(string $table, string $key): bool;

	/**
	 * Create a table
	 * @param  Table $table Table name
	 * @return bool
	 */
	public function createTable(Table $table): bool;

	/**
	 * Drop a table
	 * @param  string $table Table name
	 * @return bool
	 */
	public function dropTable(string $table);

	/**
	 * Modify a table
	 * @param  Table $table Table name
	 * @return bool
	 */
	public function alterTable(Table $table): bool;

	/**
	 * Rename a table
	 * @param  string $from Current table name
	 * @param  string $to   New table name
	 * @return bool
	 */
	public function renameTable(string $from, string $to);
}
