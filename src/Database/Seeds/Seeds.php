<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Seeds;

use Exception;
use RuntimeException;

use Caldera\Database\Database;

class Seeds {

	/**
	 * Paths array
	 * @var array
	 */
	protected $paths = [];

	/**
	 * Database instance
	 * @var Database
	 */
	protected $database;

	/**
	 * Status flag for the autoloader
	 * @var bool
	 */
	protected $loaded = false;

	/**
	 * Constructor
	 * @param Database $database Database instance
	 */
	public function __construct(Database $database) {
		$this->database = $database;
	}

	/**
	 * Add a path to search for seed files
	 * @param  string $path      Directory to search in
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
	 * Seed the database
	 * @param  string $seeder Seeder class name
	 * @return void
	 */
	public function seed(string $seeder = null): void {
		$seeders = $this->autoload();
		$available = [];
		if ($seeder) {
			if ( isset( $seeders[$seeder] ) ) {
				$available[] = $seeders[$seeder];
			}
		} else {
			$available = $seeders;
		}
		if ($seeders) {
			foreach ($seeders as $seeder) {
				if (! class_exists($seeder->class) ) {
					include $seeder->path;
				}
				try {
					$instance = new $seeder->class($this->database);
					if ( $instance instanceof SeederInterface ) {
						$instance->run();
					} else {
						throw new RuntimeException("Class '{$seeder->class}' does not implement SeederInterface");
					}
				} catch (Exception $e) {
					throw new RuntimeException('An error has ocurred when running the seeder', 0, $e);
				}
			}
		}
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
						if ( preg_match('/(.*)\.php/', $file, $matches) === 1 ) {
							$name = $matches[1];
							$class = sprintf('%s\%s', $namespace, $matches[1]);
							$ret[$name] = (object) [
								'name' => $name,
								'class' => $class,
								'path' => "{$entry->path}/{$file}"
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
