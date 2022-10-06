<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @version 1.0
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Adapter;

use PDO;
use PDOException;

use Caldera\Database\Adapter\AdapterInterface;
use Caldera\Database\Adapter\PDOAdapter;
use Caldera\Database\DatabaseException;

class MySQLAdapter extends PDOAdapter implements AdapterInterface {

	/**
	 * Connect adapter
	 * @return bool
	 */
	public function connect(): bool {
		$ret = false;
		# Get connection options
		$host = $this->options['host'] ?? '';
		$port = $this->options['port'] ?? '3306';
		$name = $this->options['name'] ?? '';
		$user = $this->options['user'] ?? '';
		$password = $this->options['password'] ?? '';
		# Build DSN
		$this->dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $host, $port, $name);
		try {
			$this->dbh = new PDO($this->dsn, $user, $password);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
			$ret = true;
		} catch (PDOException $e) {
			throw new DatabaseException($this, $e->getMessage(), (int) $e->getCode(), $e->getPrevious());
		}
		return $ret;
	}
}