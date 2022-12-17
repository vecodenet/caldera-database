<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Migrations\Migrator;

use Caldera\Database\Migrations\Migrator\AbstractMigrator;
use Caldera\Database\Schema\Schema;

class MySQLMigrator extends AbstractMigrator {

	/**
	 * Setup migrations table
	 * @return bool
	 */
	public function setup(): bool {
		$schema = new Schema($this->database);
		if (! $schema->hasTable($this->table) ) {
			$schema->create($this->table, function($table) {
				$table->bigInteger('id')->autoIncrement();
				$table->string('name', 180);
				$table->string('class', 180);
				$table->integer('batch');
				$table->dateTime('created');
				$table->dateTime('modified');
				$table->index('key_name', 'name');
				$table->index('key_class', 'class');
				$table->primary('pk_id', 'id');
			});
			return $schema->hasTable($this->table);
		}
		return false;
	}

	/**
	 * Get applied migrations
	 * @param  string $order Order, either ASC or DESC
	 * @param  int    $steps How many steps
	 * @return array
	 */
	public function getApplied(string $order = 'ASC', int $steps = -1): array {
		return $this->database->select("SELECT * FROM `{$this->table}` ORDER BY `id` {$order}" . ($steps > 0 ? " LIMIT {$steps}" : ''));
	}

	/**
	 * Get applied migrations
	 * @param  int    $batch Batch number
	 * @param  string $order Order, either ASC or DESC
	 * @param  int    $steps How many steps
	 * @return array
	 */
	public function getAppliedByBatch(int $batch, string $order = 'ASC', int $steps = -1): array {
		return $this->database->select("SELECT * FROM `{$this->table}` WHERE batch = ? ORDER BY `id` {$order}" . ($steps > 0 ? " LIMIT {$steps}" : ''), [ $batch ]);
	}

	/**
	 * Get latest batch
	 * @return int
	 */
	public function getLatestBatch(): int {
		return $this->database->scalar("SELECT max(`batch`) AS max FROM `{$this->table}`");
	}

	/**
	 * Get number of executed batches
	 * @return int
	 */
	public function getTotalBatches(): int {
		return $this->database->scalar("SELECT count(`batch`) AS count FROM `{$this->table}`");
	}

	/**
	 * Store applied migration
	 * @param  string $name  Migration name
	 * @param  string $class Migration class
	 * @param  int    $batch Batch number
	 * @return int
	 */
	public function storeMigration(string $name, string $class, int $batch): int {
		$this->database->query("INSERT INTO `{$this->table}` (`id`, `name`, `class`, `batch`, `created`, `modified`) VALUES (0, ?, ?, ?, NOW(), NOW())", [
			$name,
			$class,
			$batch,
		]);
		return $this->database->lastInsertId();
	}

	/**
	 * Delete applied migration
	 * @param  int   $id Migration id
	 * @return $this
	 */
	public function deleteMigration(int $id) {
		$this->database->query("DELETE FROM `{$this->table}` WHERE id = ?", [ $id ]);
		return $this;
	}

	/**
	 * Delete all applied migrations
	 * @return $this
	 */
	public function clearMigrations() {
		$this->database->query("TRUNCATE `{$this->table}`");
		return $this;
	}
}
