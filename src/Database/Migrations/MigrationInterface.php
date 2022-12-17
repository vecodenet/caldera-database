<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Migrations;

interface MigrationInterface {

	/**
	 * Migration up
	 * @return bool
	 */
	public function up(): bool;

	/**
	 * Migration down
	 * @return bool
	 */
	public function down(): bool;
}
