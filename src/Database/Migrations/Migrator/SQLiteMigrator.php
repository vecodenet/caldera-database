<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Migrations\Migrator;

use Caldera\Database\Migrations\Migrator\MySQLMigrator;
use Caldera\Database\Schema\Schema;

class SQLiteMigrator extends MySQLMigrator {

	/**
	 * Setup migrations table
	 * @return bool
	 */
	public function setup(): bool {
		$schema = new Schema($this->database);
		if (! $schema->hasTable($this->table) ) {
			$schema->create($this->table, function($table) {
				$table->bigInteger('id')->autoIncrement();
				$table->string('name');
				$table->string('class');
				$table->integer('batch');
				$table->dateTime('created');
				$table->dateTime('modified');
				$table->index('key_migration_name', 'name');
				$table->index('key_migration_class', 'class');
			});
			return $schema->hasTable($this->table);
		}
		return false;
	}

	/**
	 * Store applied migration
	 * @param  string $name  Migration name
	 * @param  string $class Migration class
	 * @param  int    $batch Batch number
	 * @return int
	 */
	public function storeMigration(string $name, string $class, int $batch): int {
		$this->database->query("INSERT INTO `{$this->table}` (`id`, `name`, `class`, `batch`, `created`, `modified`) VALUES (0, ?, ?, ?, DATE('now'), DATE('now'))", [
			$name,
			$class,
			$batch,
		]);
		return $this->database->lastInsertId();
	}

	/**
	 * Delete all applied migrations
	 * @return $this
	 */
	public function clearMigrations() {
		$this->database->query("DELETE FROM `{$this->table}`");
		return $this;
	}
}
