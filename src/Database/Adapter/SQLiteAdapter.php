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
use PDOException;

use Caldera\Database\Adapter\AdapterInterface;
use Caldera\Database\Adapter\PDOAdapter;
use Caldera\Database\DatabaseException;

class SQLiteAdapter extends PDOAdapter implements AdapterInterface {

	/**
	 * Connect adapter
	 * @return bool
	 */
	public function connect(): bool {
		$ret = false;
		# Get connection options
		$file = $this->options['file'] ?? '';
		if ( $file && file_exists($file) ) {
			# Build DSN
			$this->dsn = sprintf('sqlite:%s', $file);
			try {
				$this->dbh = new PDO($this->dsn);
				$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
				$ret = true;
			} catch (PDOException $e) {
				throw new DatabaseException($this, $e->getMessage(), (int) $e->getCode(), $e->getPrevious());
			}
		} else {
			throw new DatabaseException($this, 'Database file does not exist');
		}
		return $ret;
	}
}