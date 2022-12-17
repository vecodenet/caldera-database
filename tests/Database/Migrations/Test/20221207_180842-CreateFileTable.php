<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database\Migrations\Test;

use Caldera\Database\Migrations\AbstractMigration;
use Caldera\Database\Schema\Schema;
use Caldera\Database\Schema\Table;

class CreateFileTable extends AbstractMigration {

	/**
	 * Migration up
	 * @return bool
	 */
	public function up(): bool {
		$schema = new Schema($this->database);
		$schema->createIfNotExists('test', function(Table $table) {
			$table->bigInteger('id');
			$table->string('name');
			$table->string('type');
			$table->string('status');
			$table->datetime('created');
			$table->primary('pk_test_id', 'id');
		});
		return true;
	}

	/**
	 * Migration down
	 * @return bool
	 */
	public function down(): bool {
		$schema = new Schema($this->database);
		$schema->dropIfExists('test');
		return true;
	}
}
