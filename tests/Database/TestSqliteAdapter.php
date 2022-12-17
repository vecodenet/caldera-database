<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use Closure;

use Caldera\Database\Adapter\SQLiteAdapter;

class TestSqliteAdapter extends SQLiteAdapter {

	/**
	 * Executed query
	 * @var string
	 */
	protected $query = '';

	/**
	 * Bound parameters
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * Return value for scalar queries
	 * @var integer
	 */
	protected $return_scalar = 1;

	/**
	 * Transaction flag
	 * @var bool
	 */
	protected $in_transaction;

	/**
	 * PDOStatement mock
	 * @var mixed
	 */
	protected $stmt_mock;

	/**
	 * Transaction array
	 * @var array
	 */
	protected $transaction = [];

	/**
	 * Constructor
	 * @param mixed $stmt_mock PDOStatement mock
	 */
	public function __construct($stmt_mock) {
		$this->stmt_mock = $stmt_mock;
	}

	/**
	 * Get executed query
	 * @return string
	 */
	public function getQuery(): string {
		return $this->query;
	}

	/**
	 * Get bound parameters
	 * @return array
	 */
	public function getParameters(): array {
		return $this->parameters;
	}

	/**
	 * Get transaction contents
	 * @return array
	 */
	public function getTransaction(): array {
		return $this->transaction;
	}

	/**
	 * Get transaction flag
	 * @return bool
	 */
	public function inTransaction(): bool {
		return $this->in_transaction;
	}

	/**
	 * Connect adapter
	 * @return bool
	 */
	public function connect(): bool {
		return true;
	}

	/**
	 * Begin transaction
	 * @return $this
	 */
	public function begin() {
		$this->in_transaction = true;
		return $this;
	}

	/**
	 * Commit transaction
	 * @return $this
	 */
	public function commit() {
		$this->in_transaction = false;
		return $this;
	}

	/**
	 * Rollback transaction
	 * @return $this
	 */
	public function rollback() {
		$this->in_transaction = false;
		return $this;
	}

	/**
	 * Set the return value for scalar queries
	 * @param int $value Value to return
	 */
	public function setReturnScalarValue(int $value) {
		$this->return_scalar = $value;
	}

	/**
	 * Execute a query
	 * @param  string   $query      Query string
	 * @param  array    $parameters Array of parameters
	 * @param  Closure $callback   Optional callback
	 * @return mixed
	 */
	public function query(string $query, array $parameters = [], Closure $callback = null) {
		$this->query = $query;
		$this->parameters = $parameters;
		if ($this->in_transaction) {
			$this->transaction[] = [
				'query' => $query,
				'parameters' => $parameters,
			];
		}
		if ( preg_match('/(COUNT|SUM|MIN|MAX|AVG)\(.*\)\s+(?:AS\s+(.*)\s+)?FROM\s+(.*)/', $query, $matches) === 1 ) {
			$ret = [];
			$keyword = strtolower( $matches[1] );
			$alias = $matches[2] ?? null;
			$keyword = $alias ? $alias : $keyword;
			$ret[$keyword] = $this->return_scalar;
			return (object) $ret;
		} else if ( preg_match('/^SELECT `name`, `value` FROM `user_meta`/', $query, $matches) === 1 ) {
			$ret = [
				(object) [
					'name' => 'foo',
					'value' => 'bar',
				]
			];
			return $ret;
		} else if ( $query == 'SELECT * FROM `user` WHERE `id` = ?' && ($parameters[0] ?? null) == 123 ) {
			return (object) [
				'id' => 123,
				'name' => 'Test',
				'email' => 'test@example.com',
				'status' => 'Active',
				'created' => '2022-11-30 15:15:15',
				'modified' => '2022-11-30 15:15:15',
			];
		} else if ( preg_match('/^SELECT \* FROM `order` WHERE `id` > \?/', $query) === 1 ) {
			$id = (int) $parameters[0];
			if ($id < 50) {
				$ret = [];
				for ($i = 1; $i <= 10; $i++) {
					$ret[] = (object) [
						'id' => $id + $i,
						'items' => random_int(5, 10),
						'total' => random_int(1000, 2000),
						'status' => 'Pending',
					];
				}
			} else {
				$ret = [];
			}
			return $ret;
		} else if ( str_starts_with($query, "SELECT name FROM sqlite_master WHERE type ='table'") ) {
			return [
				(object) ['name' => 'foo'],
				(object) ['name' => 'bar'],
			];
		} else if ( str_starts_with($query, "SELECT * FROM pragma_table_info(?) WHERE `name` = ?") ) {
			return (object) [
				'type' => 'TEXT',
				'not_null' => 0,
				'dflt_value' => null,
			];
		} else if ( preg_match('/^SELECT/', $query, $matches) === 1 ) {
			return [];
		}
		if ( $callback ) {
			call_user_func($callback, $this->stmt_mock);
		}
		return true;
	}

	/**
	 * Get the last inserted ID
	 * @return int
	 */
	public function lastInsertId(): int {
		return 1;
	}
}
