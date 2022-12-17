<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database\Schema;

use PDOStatement;

use PHPUnit\Framework\TestCase;

use Caldera\Database\Database;
use Caldera\Database\Schema\Schema;
use Caldera\Database\Schema\Table;
use Caldera\Tests\Database\TestSqliteAdapter;

class SchemaWithSqliteAdapterTest extends TestCase {

	/**
	 * Database adapter instance
	 * @var TestSqliteAdapter
	 */
	protected static $adapter;

	/**
	 * Database instance
	 * @var Database
	 */
	protected static $database;

	protected function setUp(): void {
		/**
		 * PDOStatement mock
		 * @var Stub
		 */
		$mock = self::createStub(PDOStatement::class);
		$mock->method('fetchAll')->willReturn([]);
		$mock->method('fetch')->willReturn((object)[]);
		self::$adapter = new TestSqliteAdapter($mock);
		self::$database = new Database(self::$adapter);
	}

	public function testGetTables() {
		$schema = new Schema(self::$database);
		$tables = $schema->getTables();
		$this->assertEquals( "SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%'", self::$adapter->getQuery() );
		$this->assertEquals( ['foo', 'bar'], $tables );
		$columns = $schema->getColumns('foo');
		$keys = $schema->getKeys('foo');
		$this->assertIsArray($columns);
		$this->assertIsArray($keys);
	}

	public function testHasTable() {
		$schema = new Schema(self::$database);
		$schema->hasTable('test');
		$this->assertEquals( "SELECT COUNT(*) AS total FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%' AND name = ?", self::$adapter->getQuery() );
	}

	public function testHasColumn() {
		$schema = new Schema(self::$database);
		$schema->hasColumn('test', 'id');
		$this->assertEquals( "SELECT name FROM pragma_table_info(?) WHERE name = ?", self::$adapter->getQuery() );
	}

	public function testHasKey() {
		$schema = new Schema(self::$database);
		$schema->hasKey('test', 'pk_id');
		$this->assertEquals( "SELECT COUNT(*) AS total FROM sqlite_master WHERE type = 'index' WHERE tbl_name = ? AND name = ?", self::$adapter->getQuery() );
	}

	public function testCreate() {
		$schema = new Schema(self::$database);
		$schema->create('test', function(Table $table) {
			$table->bigInteger('id')->autoIncrement();
			$table->string('name', 120);
			$table->string('status', 50);
			$table->string('type', 50);
			$table->integer('points')->nullable()->default(0);
			$table->datetime('created')->nullable();
			$table->primary('pk_id', 'id');
			$table->index('key_name', 'name');
		});
		$transaction = self::$adapter->getTransaction();
		$query = <<<QUERY
		CREATE TABLE `test` (
		    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		    `name` TEXT NOT NULL,
		    `status` TEXT NOT NULL,
		    `type` TEXT NOT NULL,
		    `points` INTEGER NULL DEFAULT '0',
		    `created` TEXT NULL
		);
		QUERY;
		$this->assertEquals( $query, $transaction[0]['query'] );
		$this->assertEquals( "CREATE INDEX `key_name` ON `test`(`name`)", $transaction[1]['query'] );
	}

	public function testCreateIfNotExists() {
		$schema = new Schema(self::$database);
		self::$adapter->setReturnScalarValue(0);
		$schema->createIfNotExists('test', function(Table $table) {
			$table->bigInteger('id')->unsigned()->autoIncrement();
			$table->string('name')->length(120);
			$table->string('email', 120)->name('login');
			$table->double('karma')->precision([4, 2]);
			$table->datetime('created')->nullable()->defaultRaw("(DATETIME('now', 'localtime'))");
			$table->primary('pk_id', 'id');
			$table->unique('key_email', 'email');
		});
		$transaction = self::$adapter->getTransaction();
		$query = <<<QUERY
		CREATE TABLE `test` (
		    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		    `name` TEXT NOT NULL,
		    `login` TEXT NOT NULL,
		    `karma` REAL(4, 2) NOT NULL,
		    `created` TEXT NULL DEFAULT (DATETIME('now', 'localtime'))
		);
		QUERY;
		$this->assertEquals( $query, $transaction[0]['query'] );
		$this->assertEquals( "CREATE UNIQUE INDEX `key_email` ON `test`(`email`)", $transaction[1]['query'] );
		self::$adapter->setReturnScalarValue(1);
	}

	public function testDrop() {
		$schema = new Schema(self::$database);
		$schema->drop('test');
		$this->assertEquals( "DROP TABLE `test`;", self::$adapter->getQuery() );
		$ret = $schema->dropIfExists('test');
		$this->assertTrue($ret);
	}

	public function testTable() {
		$schema = new Schema(self::$database);
		$schema->table('test', function(Table $table) {
			$table->index('key')->name('key_id_email')->columns(['id', 'email']);
			$table->dropIndex('key_name');
			$table->renameColumn('login', 'email');
			$table->datetime('modified')->after('created')->nullable();
			$table->dropColumn('permissions');
		});
		$transaction = self::$adapter->getTransaction();
		$this->assertEquals( "ALTER TABLE `test` RENAME COLUMN `login` TO `email`", $transaction[0]['query'] );
		$this->assertEquals( "ALTER TABLE `test` ADD COLUMN `modified` TEXT NULL", $transaction[1]['query'] );
		$this->assertEquals( "ALTER TABLE `test` DROP COLUMN `permissions`", $transaction[2]['query'] );
		$this->assertEquals( "CREATE INDEX `key_id_email` ON `test`(`id`, `email`)", $transaction[3]['query'] );
		$this->assertEquals( "DROP INDEX `key_name`", $transaction[4]['query'] );
	}

	public function testTypes() {
		$schema = new Schema(self::$database);
		$schema->create('test', function(Table $table) {
			$table->bigInteger('col_big_integer');
			$table->binary('col_binary');
			$table->boolean('col_boolean');
			$table->char('col_char');
			$table->date('col_date');
			$table->datetime('col_datetime');
			$table->decimal('col_decimal');
			$table->double('col_double');
			$table->enum('col_enum');
			$table->float('col_float');
			$table->integer('col_integer');
			$table->json('col_json');
			$table->longText('col_long_text');
			$table->mediumInteger('col_medium_integer');
			$table->mediumText('col_medium_text');
			$table->smallInteger('col_small_integer');
			$table->tinyInteger('col_tiny_integer');
			$table->string('col_string');
			$table->text('col_text');
			$table->time('col_time');
			$table->timestamp('col_timestamp');
		});
		$query = <<<QUERY
		CREATE TABLE `test` (
		    `col_big_integer` INTEGER NOT NULL,
		    `col_binary` BLOB NOT NULL,
		    `col_boolean` INTEGER NOT NULL,
		    `col_char` TEXT NOT NULL,
		    `col_date` TEXT NOT NULL,
		    `col_datetime` TEXT NOT NULL,
		    `col_decimal` REAL(5, 2) NOT NULL,
		    `col_double` REAL(15) NOT NULL,
		    `col_enum` TEXT NOT NULL,
		    `col_float` REAL NOT NULL,
		    `col_integer` INTEGER NOT NULL,
		    `col_json` TEXT NOT NULL,
		    `col_long_text` TEXT NOT NULL,
		    `col_medium_integer` INTEGER NOT NULL,
		    `col_medium_text` TEXT NOT NULL,
		    `col_small_integer` INTEGER NOT NULL,
		    `col_tiny_integer` INTEGER NOT NULL,
		    `col_string` TEXT NOT NULL,
		    `col_text` TEXT NOT NULL,
		    `col_time` TEXT NOT NULL,
		    `col_timestamp` INTEGER NOT NULL
		);
		QUERY;
		$this->assertEquals( $query, self::$adapter->getQuery() );
	}

	public function testRename() {
		$schema = new Schema(self::$database);
		$schema->rename('test', 'foo');
		$this->assertEquals( "ALTER TABLE `test` RENAME TO `foo`;", self::$adapter->getQuery() );
	}
}
