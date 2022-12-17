<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Schema\Builder;

use Caldera\Database\Database;
use Caldera\Database\Schema\Table;

abstract class AbstractBuilder implements BuilderInterface {

	/**
	 * Database instance
	 * @var Database
	 */
	protected $database;

	/**
	 * Constructor
	 * @param Database $database Database instance
	 */
	public function __construct(Database $database) {
		$this->database = $database;
	}
}