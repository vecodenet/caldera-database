<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Migrations\Migrator;

interface MigratorInterface {

	/**
	 * Setup migrations table
	 * @return bool
	 */
	public function setup(): bool;

	/**
	 * Get applied migrations
	 * @param  string $order Order, either ASC or DESC
	 * @param  int    $steps How many steps
	 * @return array
	 */
	public function getApplied(string $order = 'ASC', int $steps = -1): array;

	/**
	 * Get applied migrations
	 * @param  int    $batch Batch number
	 * @param  string $order Order, either ASC or DESC
	 * @param  int    $steps How many steps
	 * @return array
	 */
	public function getAppliedByBatch(int $batch, string $order = 'ASC', int $steps = -1): array;

	/**
	 * Get latest batch
	 * @return int
	 */
	public function getLatestBatch(): int;

	/**
	 * Get number of executed batches
	 * @return int
	 */
	public function getTotalBatches(): int;

	/**
	 * Store applied migration
	 * @param  string $name  Migration name
	 * @param  string $class Migration class
	 * @param  int    $batch Batch number
	 * @return int
	 */
	public function storeMigration(string $name, string $class, int $batch): int;

	/**
	 * Delete applied migration
	 * @param  int   $id Migration id
	 * @return $this
	 */
	public function deleteMigration(int $id);

	/**
	 * Delete all applied migrations
	 * @return $this
	 */
	public function clearMigrations();
}
