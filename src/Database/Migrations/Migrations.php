<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Migrations;

use Exception;
use RuntimeException;

use Caldera\Database\Database;
use Caldera\Database\Adapter\MySQLAdapter;
use Caldera\Database\Adapter\SQLiteAdapter;
use Caldera\Database\Migrations\Migrator\MigratorInterface;
use Caldera\Database\Migrations\Migrator\MySQLMigrator;
use Caldera\Database\Migrations\Migrator\SQLiteMigrator;

class Migrations {

	/**
	 * Table name
	 * @var string
	 */
	protected $table = '';

	/**
	 * Paths array
	 * @var array
	 */
	protected $paths = [];

	/**
	 * Status flag for the autoloader
	 * @var bool
	 */
	protected $loaded = false;

	/**
	 * Database instance
	 * @var Database
	 */
	protected $database;

	/**
	 * Migrator instance
	 * @var MigratorInterface
	 */
	protected $migrator;

	/**
	 * Constructor
	 * @param Database $database Database instance
	 * @param string   $table    Table name
	 */
	public function __construct(Database $database, string $table = 'migration') {
		$this->database = $database;
		$this->table = $table;
		# Now get an adapter instance
		$adapter = $this->database->getAdapter();
		# Check for subclasses
		if ( $adapter instanceof MySQLAdapter ) {
			$this->migrator = new MySQLMigrator($this->database, $this->table);
		} else if ( $adapter instanceof SQLiteAdapter ) {
			$this->migrator = new SQLiteMigrator($this->database, $this->table);
		} else {
			throw new RuntimeException( sprintf( "Unsupported adapter '%s'", get_class($adapter) ) );
		}
	}

	/**
	 * Add a path to search for migration files
	 * @param  string $path Directory to search in
	 * @return $this
	 */
	public function path(string $path) {
		if ( file_exists($path) && is_dir($path) ) {
			$this->paths[] = (object) [
				'path' => $path
			];
			$this->loaded = false;
		} else {
			throw new RuntimeException('The specified path does not exist');
		}
		return $this;
	}

	/**
	 * Setup migrations support
	 * @return bool
	 */
	public function setup(): bool {
		return $this->migrator->setup();
	}

	/**
	 * Get migration status
	 * @return mixed
	 */
	public function status() {
		$ret = null;
		$applied = $this->migrator->getApplied();
		$available = [];
		$migrations = $this->autoload();
		if ($applied) {
			$temp = [];
			foreach ($applied as $migration) {
				$temp[] = $migration->name;
			}
			$applied = $temp;
		}
		if ($migrations) {
			foreach ($migrations as $name => $migration) {
				if ( in_array($name, $applied) ) continue;
				$available[] = $name;
			}
		}
		$ret = [
			'applied' => $applied,
			'available' => $available
		];
		return (object) $ret;
	}

	/**
	 * Execute pending migrations
	 * @return void
	 */
	public function migrate(): void {
		$applied = $this->migrator->getApplied();
		$available = [];
		$migrations = $this->autoload();
		if ($applied) {
			$temp = [];
			foreach ($applied as $migration) {
				$temp[$migration->name] = $migration;
			}
			$applied = $temp;
		}
		if ($migrations) {
			foreach ($migrations as $name => $migration) {
				if ( isset( $applied[$name] ) ) continue;
				$available[$name] = $migration;
			}
		}
		if ($available) {
			$batches = $this->migrator->getLatestBatch();
			foreach ($available as $name => $migration) {
				if ( file_exists($migration->path) ) {
					if (! class_exists($migration->class) ) {
						# Auto load migration
						require $migration->path;
					}
					$instance = new $migration->class($this->database);
					if ( $instance instanceof MigrationInterface ) {
						try {
							$result = $instance->up();
							if ($result === true) {
								$this->migrator->storeMigration($name, $migration->class, $batches + 1);
							}
						} catch (Exception $e) {
							throw new RuntimeException('An error has ocurred when running the migration', 0, $e);
						}
					} else {
						throw new RuntimeException("Class '{$migration->class}' does not implement MigrationInterface");
					}
				} else {
					throw new RuntimeException("Migration '{$migration->name}' does not exist");
				}
			}
		}
	}

	/**
	 * Rollback applied migrations
	 * @param  int $steps How many steps to rollback
	 * @return void
	 */
	public function rollback($steps = 0): void {
		if ($steps) {
			if ($steps == -1) {
				# Rollback ALL migrations
				$applied = $this->migrator->getApplied('DESC');
			} else {
				# Rollback by number of migrations
				$applied = $this->migrator->getApplied('DESC', $steps);
			}
		} else {
			# Rollback last batch of migrations
			$batch = $this->migrator->getLatestBatch();
			$applied = $this->migrator->getAppliedByBatch($batch);
		}
		$available = [];
		$migrations = $this->autoload();
		if ($applied) {
			$temp = [];
			foreach ($applied as $migration) {
				$temp[$migration->name] = $migration;
			}
			$applied = $temp;
		}
		$missing = [];
		if ($applied) {
			foreach ($applied as $migration) {
				if ( isset( $migrations[$migration->name] ) ) {
					$temp = $migrations[$migration->name];
					$temp->id = $migration->id;
					$available[] = $temp;
				} else {
					$missing[] = $applied['name'];
				}
			}
		}
		if ($missing) {
			$temp = implode(', ', $missing);
			throw new RuntimeException("Missing migrations that can not be rolled-back: {$temp}");
		}
		if ($available) {
			foreach ($available as $migration) {
				if ( file_exists($migration->path) ) {
					if (! class_exists($migration->class) ) {
						# Auto load migration
						include $migration->path;
					}
					$instance = new $migration->class($this->database);
					if ( $instance instanceof MigrationInterface ) {
						try {
							$result = $instance->down();
							if ($result === true) {
								$this->migrator->deleteMigration($migration->id);
							}
						} catch (Exception $e) {
							throw new RuntimeException('An error has ocurred when running the migration', 0, $e);
						}
					} else {
						throw new RuntimeException("Class '{$migration->class}' does not implement MigrationInterface");
					}
				} else {
					throw new RuntimeException("Migration '{$migration->name}' does not exist");
				}
			}
			if ($steps == -1) {
				$count = $this->migrator->getTotalBatches();
				if ( $count == 0 ) {
					# To reset primary keys
					$this->migrator->clearMigrations();
				}
			}
		}
	}

	/**
	 * Rollback all migrations and run them again
	 * @return $this
	 */
	public function reset() {
		$this->rollback(-1);
		$this->migrate();
		return $this;
	}

	/**
	 * Autoload migrations from the registered directories
	 * @return array
	 */
	protected function autoload(): array {
		$ret = [];
		# Iterate the registered paths
		if ($this->paths) {
			foreach ($this->paths as $entry) {
				$files = scandir($entry->path, SCANDIR_SORT_ASCENDING);
				if ($files) {
					# And check the files
					foreach ($files as $file) {
						if ( $file == '.' || $file == '..' ) continue;
						$path = "{$entry->path}/{$file}";
						$namespace = $this->getNamespace($path);
						if ( preg_match('/(\d{8}_\d{6})-(.*)\.php/', $file, $matches) === 1 ) {
							$date = $matches[1];
							$name = $matches[2];
							$class = sprintf('%s\%s', $namespace, $matches[2]);
							$key = "{$date}-{$name}";
							$ret[$key] = (object) [
								'date' => $date,
								'name' => $name,
								'class' => $class,
								'path' => $path
							];
						}
					}
				}
			}
		}
		$this->loaded = true;
		return $ret;
	}

	/**
	 * Get namespace from file
	 * @param  string $path Path to class
	 * @return string
	 */
	protected function getNamespace(string $path): string {
		$src = file_exists($path) ? file_get_contents($path) : '';
		if ($src) {
			$tokens = token_get_all($src);
			$count = count($tokens);
			$i = 0;
			$namespace = '';
			$namespace_found = false;
			while ($i < $count) {
				$token = $tokens[$i];
				if (is_array($token) && $token[0] === T_NAMESPACE) {
					// Found namespace declaration
					while (++$i < $count) {
						if ($tokens[$i] === ';') {
							$namespace_found = true;
							$namespace = trim($namespace);
							break;
						}
						$namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
					}
					break;
				}
				$i++;
			}
			if (!$namespace_found) {
				return '';
			} else {
				return $namespace;
			}
		}
		return '';
	}
}
