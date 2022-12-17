<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Schema;

use Caldera\Database\Schema\Column;
use Caldera\Database\Schema\Key;

class Table {

	/**
	 * Table name
	 * @var string
	 */
	protected $name;

	/**
	 * Columns array
	 * @var array
	 */
	protected $columns = [];

	/**
	 * Keys array
	 * @var array
	 */
	protected $keys = [];

	/**
	 * Constructor
	 * @param string $name Table name
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * Set table name
	 * @param  string $name Table name
	 * @return $this
	 */
	public function name(string $name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get table name
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get columns array
	 * @return array
	 */
	public function getColumns(): array{
		return $this->columns;
	}

	/**
	 * Get keys array
	 * @return array
	 */
	public function getKeys(): array {
		return $this->keys;
	}

	/**
	 * Add a new column
	 * @param  string $name Column name
	 * @param  string $type Column type
	 * @return Column
	 */
	public function column(string $name, string $type): Column {
		$column = new Column($name);
		$column->type($type);
		$this->columns[] = $column;
		return $column;
	}

	/**
	 * Add a new key
	 * @param  string $name Key name
	 * @param  string $type Key type
	 * @return Key
	 */
	public function key(string $name, string $type): Key {
		$key = new Key($name);
		$key->type($type);
		$this->keys[] = $key;
		return $key;
	}

	/**
	 * Add a BIGINT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function bigInteger(string $name): Column {
		return $this->column($name, Column::TYPE_BIGINT);
	}

	/**
	 * Add a BINARY or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function binary(string $name): Column {
		return $this->column($name, Column::TYPE_BINARY);
	}

	/**
	 * Add a BOOLEAN or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function boolean(string $name): Column {
		return $this->column($name, Column::TYPE_BOOLEAN);
	}

	/**
	 * Add a CHAR or equivalent column
	 * @param  string $name   Column name
	 * @param  int    $length Column length
	 * @return Column
	 */
	public function char(string $name, int $length = 100): Column {
		$column = $this->column($name, Column::TYPE_CHAR);
		$column->length($length);
		return $column;
	}

	/**
	 * Add a DATE or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function date(string $name): Column {
		return $this->column($name, Column::TYPE_DATE);
	}

	/**
	 * Add a DATETIME or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function datetime(string $name): Column {
		return $this->column($name, Column::TYPE_DATETIME);
	}

	/**
	 * Add a DECIMAL or equivalent column
	 * @param  string $name       Column name
	 * @param  int    $precision Number of significant digits
	 * @param  int    $scale     Number of digits after the decimal point
	 * @return Column
	 */
	public function decimal(string $name, int $precision = 5, int $scale = 2): Column {
		$column = $this->column($name, Column::TYPE_DECIMAL);
		$column->precision([$precision, $scale]);
		return $column;
	}

	/**
	 * Add a DOUBLE or equivalent column
	 * @param  string  $name      Column name
	 * @param  int    $precision Number of significant digits
	 * @return Column
	 */
	public function double(string $name, int $precision = 15): Column {
		$column = $this->column($name, Column::TYPE_DOUBLE);
		$column->precision($precision);
		return $column;
	}

	/**
	 * Add an ENUM or equivalent column
	 * @param  string $name    Column name
	 * @param  array  $options Options for the enum
	 * @return Column
	 */
	public function enum(string $name, array $options = []): Column {
		$column = $this->column($name, Column::TYPE_ENUM);
		$column->options($options);
		return $column;
	}

	/**
	 * Add a FLOAT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function float(string $name): Column {
		return $this->column($name, Column::TYPE_FLOAT);
	}

	/**
	 * Add an INT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function integer(string $name): Column {
		return $this->column($name, Column::TYPE_INT);
	}

	/**
	 * Add a JSON or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function json(string $name): Column {
		return $this->column($name, Column::TYPE_JSON);
	}

	/**
	 * Add a LONGTEXT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function longText(string $name): Column {
		return $this->column($name, Column::TYPE_LONGTEXT);
	}

	/**
	 * Add a MEDIUMINT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function mediumInteger(string $name): Column {
		return $this->column($name, Column::TYPE_MEDIUMINT);
	}

	/**
	 * Add a MEDIUMTEXT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function mediumText(string $name): Column {
		return $this->column($name, Column::TYPE_MEDIUMTEXT);
	}

	/**
	 * Add a SMALLINT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function smallInteger(string $name): Column {
		return $this->column($name, Column::TYPE_SMALLINT);
	}

	/**
	 * Add a TINYINT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function tinyInteger(string $name): Column {
		return $this->column($name, Column::TYPE_TINYINT);
	}

	/**
	 * Add a STRING or equivalent column
	 * @param  string $name   Column name
	 * @param  int    $length Column length
	 * @return Column
	 */
	public function string(string $name, int $length = 100): Column {
		$column = $this->column($name, Column::TYPE_STRING);
		$column->length($length);
		return $column;
	}

	/**
	 * Add a TEXT or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function text(string $name): Column {
		return $this->column($name, Column::TYPE_TEXT);
	}

	/**
	 * Add a TIME or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function time(string $name): Column {
		return $this->column($name, Column::TYPE_TIME);
	}

	/**
	 * Add a TIMESTAMP or equivalent column
	 * @param  string $name Column name
	 * @return Column
	 */
	public function timestamp(string $name): Column {
		return $this->column($name, Column::TYPE_TIMESTAMP);
	}

	/**
	 * Add an INDEX or equivalent key
	 * @param  string $name    Key name
	 * @param  mixed  $columns Key columns
	 * @return Key
	 */
	public function index(string $name, $columns = []): Key {
		$key = $this->key($name, Key::TYPE_INDEX);
		$key->columns($columns);
		return $key;
	}

	/**
	 * Add a PRIMARY or equivalent key
	 * @param  string $name    Key name
	 * @param  mixed  $columns Key columns
	 * @return Key
	 */
	public function primary(string $name, $columns = []): Key {
		$key = $this->key($name, Key::TYPE_PRIMARY);
		$key->columns($columns);
		return $key;
	}

	/**
	 * Add a UNIQUE or equivalent key
	 * @param  string $name    Key name
	 * @param  mixed  $columns Key columns
	 * @return Key
	 */
	public function unique(string $name, $columns = []): Key {
		$key = $this->key($name, Key::TYPE_UNIQUE);
		$key->columns($columns);
		return $key;
	}

	/**
	 * Add a FOREIGN or equivalent key
	 * @param  string $name    Key name
	 * @param  mixed  $columns Key columns
	 * @return Key
	 */
	public function foreign(string $name, $columns = []): Key {
		$key = $this->key($name, Key::TYPE_FOREIGN);
		$key->columns($columns);
		return $key;
	}

	/**
	 * Rename a column
	 * @param  string $from From name
	 * @param  string $to   To name
	 * @return $this
	 */
	public function renameColumn(string $from, string $to) {
		$this->column($from, '{TYPE}')->rename($to);
		return $this;
	}

	/**
	 * Delete a column
	 * @param  string $name Column name
	 * @return $this
	 */
	public function dropColumn(string $name) {
		$this->column($name, Column::TYPE_INT)->drop();
		return $this;
	}

	/**
	 * Delete a key
	 * @param  string $name Key name
	 * @return $this
	 */
	public function dropKey(string $name) {
		$this->key($name, Key::TYPE_INDEX)->drop();
		return $this;
	}

	/**
	 * Delete an INDEX key
	 * @param  string $name Key name
	 * @return $this
	 */
	public function dropIndex(string $name) {
		$this->key($name, Key::TYPE_INDEX)->drop();
		return $this;
	}

	/**
	 * Delete a PRIMARY key
	 * @param  string $name Key name
	 * @return $this
	 */
	public function dropPrimary(string $name) {
		$this->key($name, Key::TYPE_PRIMARY)->drop();
		return $this;
	}

	/**
	 * Delete an UNIQUE key
	 * @param  string $name Key name
	 * @return $this
	 */
	public function dropUnique(string $name) {
		$this->key($name, Key::TYPE_UNIQUE)->drop();
		return $this;
	}

	/**
	 * Delete an FOREIGN key
	 * @param  string $name Key name
	 * @return $this
	 */
	public function dropForeign(string $name) {
		$this->key($name, Key::TYPE_FOREIGN)->drop();
		return $this;
	}
}
