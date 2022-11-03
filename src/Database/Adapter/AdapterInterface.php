<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Adapter;

use Closure;

interface AdapterInterface {

	/**
	 * Connect adapter
	 * @return bool
	 */
	public function connect(): bool;

	/**
	 * Execute a query
	 * @param  string  $query      Query string
	 * @param  array   $parameters Array of parameters
	 * @param  Closure $callback   Optional callback
	 * @return mixed
	 */
	public function query(string $query, array $parameters = [], Closure $callback = null);

	/**
	 * Begin a transaction
	 * @return $this
	 */
	public function begin();

	/**
	 * Commit transaction
	 * @return $this
	 */
	public function commit();

	/**
	 * Rollback transaction
	 * @return $this
	 */
	public function rollback();

	/**
	 * Get the last inserted ID
	 * @return int
	 */
	public function lastInsertId(): int;

	/**
	 * Check if there is an active connection
	 * @return bool
	 */
	public function isConnected(): bool;
}
