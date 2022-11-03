<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Adapter;

use PDO;
use Closure;
use PDOException;

use Caldera\Database\Adapter\AdapterInterface;
use Caldera\Database\DatabaseException;

abstract class PDOAdapter implements AdapterInterface {

	/**
	 * DSN
	 * @var string
	 */
	protected $dsn;

	/**
	 * Database handle
	 * @var PDO
	 */
	protected $dbh;

	/**
	 * Options array
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor
	 * @param array $options Connection options array
	 */
	public function __construct(array $options) {
		$this->options = $options;
	}

	/**
	 * Connect adapter
	 * @return bool
	 */
	public abstract function connect(): bool;

	/**
	 * Execute a query
	 * @param  string   $query      Query string
	 * @param  array    $parameters Array of parameters
	 * @param  Closure $callback   Optional callback
	 * @return mixed
	 */
	public function query(string $query, array $parameters = [], Closure $callback = null) {
		$ret = false;
		if ($this->dbh != null) {
			try {
				$stmt = $this->dbh->prepare($query);
				if ($parameters) {
					$index = 1;
					$is_numeric = isset( $parameters[0] );
					foreach ($parameters as $name => $value) {
						$stmt->bindValue($is_numeric ? $index : $name, $value, is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
						$index++;
					}
				}
				$stmt->execute();
				$result = null;
				if ( $callback ) {
					$result = call_user_func($callback, $stmt);
				}
				if ($result === null) {
					if ( stripos($query, "SELECT") === 0 ) {
						$ret = $stmt->fetchAll();
					} else {
						$ret = true;
					}
				} else {
					$ret = $result;
				}
			} catch (PDOException $e) {
				throw new DatabaseException($this, $e->getMessage(), (int) $e->getCode(), $e->getPrevious());
			}
		}
		return $ret;
	}

	/**
	 * Begin transaction
	 * @return $this
	 */
	public function begin() {
		if ($this->dbh != null) {
			try {
				$this->dbh->beginTransaction();
			} catch (PDOException $e) {
				throw new DatabaseException($this, $e->getMessage(), (int) $e->getCode(), $e->getPrevious());
			}
		}
		return $this;
	}

	/**
	 * Commit transaction
	 * @return $this
	 */
	public function commit() {
		if ($this->dbh != null) {
			if ( $this->dbh->inTransaction() ) {
				$this->dbh->commit();
			}
		}
		return $this;
	}

	/**
	 * Rollback transaction
	 * @return $this
	 */
	public function rollback() {
		if ($this->dbh != null) {
			if ( $this->dbh->inTransaction() ) {
				$this->dbh->rollBack();
			}
		}
		return $this;
	}

	/**
	 * Get the last inserted ID
	 * @return int
	 */
	public function lastInsertId(): int {
		$ret = 0;
		if ($this->dbh != null) {
			$ret = (int) $this->dbh->lastInsertId();
		}
		return $ret;
	}

	/**
	 * Check if there is an active connection
	 * @return bool
	 */
	public function isConnected(): bool {
		return $this->dbh != null;
	}
}
