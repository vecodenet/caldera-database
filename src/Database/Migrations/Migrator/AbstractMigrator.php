<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Migrations\Migrator;

use Caldera\Database\Database;
use Caldera\Database\Migrations\Migrator\MigratorInterface;

abstract class AbstractMigrator implements MigratorInterface {

	/**
	 * Database instance
	 * @var Database
	 */
	protected $database;

	/**
	 * Table name
	 * @var string
	 */
	protected $table = '';

	/**
	 * Constructor
	 * @param Database $database Database instance
	 * @param string   $table    Table name
	 */
	function __construct(Database $database, string $table ) {
		$this->database = $database;
		$this->table = $table;
	}
}
