<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Schema;

class Column {

	/**
	 * Column name
	 * @var mixed
	 */
	protected $name;

	/**
	 * Column type
	 * @var string
	 */
	protected $type;

	/**
	 * Column operation
	 * @var string
	 */
	protected $operation;

	/**
	 * Column position
	 * @var string
	 */
	protected $after = '';

	/**
	 * Column length
	 * @var mixed
	 */
	protected $length = 0;

	/**
	 * Column precision
	 * @var mixed
	 */
	protected $precision = 0;

	/**
	 * Column options
	 * @var mixed
	 */
	protected $options = 0;

	/**
	 * Nullable flag
	 * @var bool
	 */
	protected $nullable = false;

	/**
	 * Auto-increment flag
	 * @var bool
	 */
	protected $autoIncrement = false;

	/**
	 * Unsigned flag
	 * @var bool
	 */
	protected $unsigned = false;

	/**
	 * Column default
	 * @var mixed
	 */
	protected $default;

	const TYPE_BIGINT = 'bigint';
	const TYPE_BINARY = 'binary';
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_CHAR = 'char';
	const TYPE_DATE = 'date';
	const TYPE_DATETIME = 'datetime';
	const TYPE_DECIMAL = 'decimal';
	const TYPE_DOUBLE = 'double';
	const TYPE_ENUM = 'enum';
	const TYPE_FLOAT = 'float';
	const TYPE_INT = 'int';
	const TYPE_JSON = 'json';
	const TYPE_LONGTEXT = 'longtext';
	const TYPE_MEDIUMINT = 'mediumint';
	const TYPE_MEDIUMTEXT = 'mediumtext';
	const TYPE_SMALLINT = 'smallint';
	const TYPE_STRING = 'string';
	const TYPE_TEXT = 'text';
	const TYPE_TIME = 'time';
	const TYPE_TIMESTAMP = 'timestamp';
	const TYPE_TINYINT = 'tinyint';

	const OPERATION_ADD    = 'add';
	const OPERATION_MODIFY = 'modify';
	const OPERATION_RENAME = 'rename';
	const OPERATION_DROP   = 'drop';

	/**
	 * Constructor
	 * @param string $name Column name
	 */
	public function __construct(string $name) {
		$this->name = $name;
		$this->operation = Column::OPERATION_ADD;
	}

	/**
	 * Set column name
	 * @param  string $name Column name
	 * @return $this
	 */
	public function name(string $name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Set column position
	 * @param  string $after Name of the column after which this column is being inserted/moved
	 * @return $this
	 */
	public function after(string $after) {
		$this->after = $after;
		return $this;
	}

	/**
	 * Set column type
	 * @param  string $type Column type
	 * @return $this
	 */
	public function type(string $type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * Set column length
	 * @param  mixed $length Column length
	 * @return $this
	 */
	public function length($length) {
		$this->length = $length;
		return $this;
	}

	/**
	 * Set column precision
	 * @param  mixed $precision Column precision
	 * @return $this
	 */
	public function precision($precision) {
		$this->precision = $precision;
		return $this;
	}

	/**
	 * Set column options
	 * @param  mixed $options Column options
	 * @return $this
	 */
	public function options($options) {
		$this->options = $options;
		return $this;
	}

	/**
	 * Set column nullable flag
	 * @param  bool $nullable Column nullable flag
	 * @return $this
	 */
	public function nullable(bool $nullable = true) {
		$this->nullable = $nullable;
		return $this;
	}

	/**
	 * Set column unsigned flag
	 * @param  bool $unsigned Column unsigned flag
	 * @return $this
	 */
	public function unsigned(bool $unsigned = true) {
		$this->unsigned = $unsigned;
		return $this;
	}

	/**
	 * Set column auto increment flag
	 * @param  bool $autoIncrement Column auto increment flag
	 * @return $this
	 */
	public function autoIncrement(bool $autoIncrement = true) {
		$this->autoIncrement = $autoIncrement;
		return $this;
	}

	/**
	 * Set column default, escaped
	 * @param  mixed $default Column default
	 * @return $this
	 */
	public function default($default) {
		$this->default = $default;
		return $this;
	}

	/**
	 * Set column default, raw
	 * @param  mixed $default Column default
	 * @return $this
	 */
	public function defaultRaw($default) {
		$this->default = (object) [
			'value' => $default
		];
		return $this;
	}

	/**
	 * Set column operation flag to modify
	 * @return $this
	 */
	public function modify() {
		$this->operation = Column::OPERATION_MODIFY;
		return $this;
	}

	/**
	 * Set column operation flag to rename
	 * @param  string $name New column name
	 * @return $this
	 */
	public function rename(string $name) {
		$this->name = [$this->name, $name];
		$this->operation = Column::OPERATION_RENAME;
		return $this;
	}

	/**
	 * Set column operation flag to drop
	 * @return $this
	 */
	public function drop() {
		$this->operation = Column::OPERATION_DROP;
		return $this;
	}

	/**
	 * Get column name
	 * @return string
	 */
	public function getName(): string {
		return is_array($this->name) ? $this->name[1] : $this->name;
	}

	/**
	 * Get column old name, if a rename is pending
	 * @return string
	 */
	public function getOldName(): string {
		return is_array($this->name) ? $this->name[0] : $this->name;
	}

	/**
	 * Get the column after which this column is being inserted/moved
	 * @return string
	 */
	public function getAfter(): string {
		return $this->after;
	}

	/**
	 * Get column type
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Get column length
	 * @return mixed
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Get column precision
	 * @return mixed
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * Get column options
	 * @return mixed
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Get column default
	 * @return mixed
	 */
	public function getDefault() {
		return $this->default;
	}

	/**
	 * Get column operation flag
	 * @return string
	 */
	public function getOperation(): string {
		return $this->operation;
	}

	/**
	 * Get column nullable flag
	 * @return bool
	 */
	public function isNullable(): bool {
		return $this->nullable;
	}

	/**
	 * Get column unsigned flag
	 * @return bool
	 */
	public function isUnsigned(): bool {
		return $this->unsigned;
	}

	/**
	 * Get column auto-increment flag
	 * @return bool
	 */
	public function isAutoIncrement(): bool {
		return $this->autoIncrement;
	}
}
