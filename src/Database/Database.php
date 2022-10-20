<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @version 1.0
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database;

use Closure;
use Exception;

use Caldera\Database\Adapter\AdapterInterface;

class Database {

	/**
	 * Adapter instance
	 * @var AdapterInterface
	 */
	protected $adapter;

	/**
	 * Constructor
	 * @param AdapterInterface $adapter Adapter instance
	 */
	public function __construct(AdapterInterface $adapter) {
		$this->adapter = $adapter;
		$this->adapter->connect();
	}

	/**
	 * Get adapter instance
	 * @return AdapterInterface
	 */
	public function getAdapter(): AdapterInterface {
		return $this->adapter;
	}

	/**
	 * Execute a query
	 * @param  string  $query      Query string
	 * @param  array   $parameters Array of parameters
	 * @param  Closure $callback   Optional callback
	 * @return mixed
	 */
	public function query(string $query, array $parameters = [], Closure $callback = null) {
		$ret = false;
		if ($this->adapter != null) {
			$ret = $this->adapter->query($query, $parameters, $callback);
		}
		return $ret;
	}

	/**
	 * Execute a SELECT query
	 * @param  string $query      Query string
	 * @param  array  $parameters Array of parameters
	 * @return array
	 */
	public function select(string $query, array $parameters = []): array {
		$ret = [];
		if ($this->adapter != null) {
			$ret = $this->adapter->query($query, $parameters, function($stmt) {
				return $stmt->fetchAll();
			});
		}
		return $ret;
	}

	/**
	 * Execute a SELECT query and return a scalar value
	 * @param  string $query      Query string
	 * @param  array  $parameters Array of parameters
	 * @return int
	 */
	public function scalar(string $query, array $parameters = []): int {
		$ret = 0;
		if ($this->adapter != null) {
			$row = $this->adapter->query($query, $parameters, function($stmt) {
				return $stmt->fetch();
			});
			$props = $row === false ? [] : get_object_vars($row);
			if ( count($props) == 1 ) {
				$ret = reset($props);
			} else {
				throw new DatabaseException($this->adapter, "The specified query didn't return an scalar value");
			}
		}
		return $ret;
	}

	/**
	 * Execute a SELECT query, chunked
	 * @param  int     $size       Chunk size
	 * @param  string  $query      Query string
	 * @param  array   $parameters Array of parameters
	 * @param  Closure $callback   Callback for chunk processing
	 * @param  string  $id         Name of ID column
	 * @return int
	 */
	public function chunk(int $size, string $query, array $parameters, Closure $callback, string $id = 'id'): int {
		$ret = 0;
		if ($this->adapter != null) {
			$last_id = 0;
			$chunk = 1;
			$query .= str_contains(strtoupper($query), ' WHERE ') ? ' AND {chunk}' : ' WHERE {chunk}';
			do {
				$loop_query = str_replace('{chunk}', "{$id} > {$last_id} LIMIT {$size}", $query);
				$rows = $this->adapter->query($loop_query, $parameters);
				if ($rows) {
					$count = count($rows);
					if ( is_callable($callback) ) {
						$continue = $callback($rows, $count, $chunk);
						if ($continue === false) {
							break;
						}
					}
					$last_id = $rows[$count - 1]->id;
					$chunk++;
				} else {
					$chunk--;
					break;
				}
			} while ($rows);
			$ret = $chunk;
		}
		return $ret;
	}

	/**
	 * Execute a transaction
	 * @param  Closure $callback   Transaction callback
	 * @return bool
	 */
	public function transaction(Closure $callback) {
		$ret = false;
		if ($this->adapter != null) {
			$this->adapter->begin();
			try {
				$callback($this);
				$this->adapter->commit();
				$ret = true;
			} catch (Exception $e) {
				$this->adapter->rollback();
			}
		}
		return $ret;
	}

	/**
	 * Begin a transaction
	 * @return $this
	 */
	public function begin() {
		if ($this->adapter != null) {
			$this->adapter->begin();
		}
		return $this;
	}

	/**
	 * Commit transaction
	 * @return $this
	 */
	public function commit() {
		if ($this->adapter != null) {
			$this->adapter->commit();
		}
		return $this;
	}

	/**
	 * Rollback transaction
	 * @return $this
	 */
	public function rollback() {
		if ($this->adapter != null) {
			$this->adapter->rollback();
		}
		return $this;
	}

	/**
	 * Get the last inserted ID
	 * @return int
	 */
	public function lastInsertId(): int {
		$ret = 0;
		if ($this->adapter != null) {
			$ret = $this->adapter->lastInsertId();
		}
		return $ret;
	}

	/**
	 * Check if there is an active connection
	 * @return bool
	 */
	public function isConnected(): bool {
		$ret = false;
		if ($this->adapter != null) {
			$ret = $this->adapter->isConnected();
		}
		return $ret;
	}
}
