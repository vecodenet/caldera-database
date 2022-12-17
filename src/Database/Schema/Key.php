<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Schema;

class Key {

	/**
	 * Key name
	 * @var string
	 */
	protected $name;

	/**
	 * Key type
	 * @var string
	 */
	protected $type;

	/**
	 * Key operation
	 * @var string
	 */
	protected $operation;

	/**
	 * Key columns
	 * @var array
	 */
	protected $columns = [];

	const TYPE_INDEX   = 'index';
	const TYPE_PRIMARY = 'primary';
	const TYPE_UNIQUE  = 'unique';
	const TYPE_FOREIGN = 'foreign';

	const OPERATION_ADD    = 'add';
	const OPERATION_DROP   = 'drop';

	/**
	 * Constructor
	 * @param string $name Key name
	 */
	function __construct(string $name) {
		$this->name = $name;
		$this->operation = Key::OPERATION_ADD;
	}

	/**
	 * Set key name
	 * @param  string $name Key name
	 * @return $this
	 */
	public function name(string $name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Set key type
	 * @param  string $type Key type
	 * @return $this
	 */
	public function type(string $type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * Set key columns
	 * @param  mixed $columns Key columns
	 * @return $this
	 */
	public function columns($columns) {
		if ( is_string($columns) ) {
			$this->columns[] = $columns;
		} else {
			$this->columns = $columns;
		}
		return $this;
	}

	/**
	 * Set key operation flag to drop
	 * @return $this
	 */
	public function drop() {
		$this->operation = Key::OPERATION_DROP;
		return $this;
	}

	/**
	 * Get key name
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get key type
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Get key columns
	 * @return array
	 */
	public function getColumns(): array {
		return $this->columns;
	}

	/**
	 * Get key operation flag
	 * @return string
	 */
	public function getOperation(): string {
		return $this->operation;
	}
}
