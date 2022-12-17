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
use Caldera\Tests\Database\TestMySqlAdapter;

class SchemaWithMysqlAdapterTest extends TestCase {

	/**
	 * Database adapter instance
	 * @var TestMySqlAdapter
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
		self::$adapter = new TestMySqlAdapter($mock);
		self::$database = new Database(self::$adapter);
	}

	public function testGetTables() {
		$schema = new Schema(self::$database);
		$tables = $schema->getTables();
		$this->assertEquals( "SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = DATABASE()", self::$adapter->getQuery() );
		$this->assertEquals( ['foo', 'bar'], $tables );
		$columns = $schema->getColumns('foo');
		$keys = $schema->getKeys('foo');
		$this->assertIsArray($columns);
		$this->assertIsArray($keys);
	}

	public function testHasTable() {
		$schema = new Schema(self::$database);
		$schema->hasTable('test');
		$this->assertEquals( "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()", self::$adapter->getQuery() );
	}

	public function testHasColumn() {
		$schema = new Schema(self::$database);
		$schema->hasColumn('test', 'id');
		$this->assertEquals( "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?", self::$adapter->getQuery() );
	}

	public function testHasKey() {
		$schema = new Schema(self::$database);
		$schema->hasKey('test', 'pk_id');
		$this->assertEquals( "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE() AND INDEX_NAME = ?", self::$adapter->getQuery() );
	}

	public function testCreate() {
		$schema = new Schema(self::$database);
		$schema->create('foo', function(Table $table) {
			$table->name('test');
			$table->bigInteger('id')->autoIncrement();
			$table->string('name', 120);
			$table->string('status', 50);
			$table->string('type', 50);
			$table->integer('points')->nullable()->default(0);
			$table->datetime('created')->nullable();
			$table->primary('pk_id', 'id');
		});
		$query = <<<QUERY
		CREATE TABLE `test` (
		    `id` BIGINT NOT NULL AUTO_INCREMENT,
		    `name` VARCHAR(120) NOT NULL,
		    `status` VARCHAR(50) NOT NULL,
		    `type` VARCHAR(50) NOT NULL,
		    `points` INT NULL DEFAULT '0',
		    `created` DATETIME NULL,
		    PRIMARY KEY `pk_id` (`id`)
		);
		QUERY;
		$this->assertEquals( $query, self::$adapter->getQuery() );
	}

	public function testCreateIfNotExists() {
		$schema = new Schema(self::$database);
		self::$adapter->setReturnScalarValue(0);
		$schema->createIfNotExists('test', function(Table $table) {
			$table->bigInteger('id')->unsigned()->autoIncrement();
			$table->string('name')->length(120);
			$table->string('email', 120)->name('login');
			$table->double('karma')->precision([4, 2]);
			$table->datetime('created')->nullable()->defaultRaw('NOW()');
			$table->primary('pk_id', 'id');
			$table->foreign('fk_email', 'email');
		});
		$query = <<<QUERY
		CREATE TABLE `test` (
		    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		    `name` VARCHAR(120) NOT NULL,
		    `login` VARCHAR(120) NOT NULL,
		    `karma` DOUBLE(4, 2) NOT NULL,
		    `created` DATETIME NULL DEFAULT NOW(),
		    PRIMARY KEY `pk_id` (`id`),
		    FOREIGN KEY `fk_email` (`email`)
		);
		QUERY;
		$this->assertEquals( $query, self::$adapter->getQuery() );
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
			$table->index('pk')->name('pk_id_email')->columns(['id', 'email']);
			$table->dropIndex('pk_id');
			$table->renameColumn('login', 'email');
			$table->datetime('modified')->after('created')->nullable();
			$table->string('type')->modify()->default('Subscriber')->nullable();
			$table->dropColumn('permissions');
		});
		$query = <<<QUERY
		ALTER TABLE `test`
		    CHANGE COLUMN `login` `email` VARCHAR NOT NULL,
		    ADD `modified` DATETIME NULL AFTER `created`,
		    CHANGE COLUMN `type` `type` VARCHAR(100) NULL DEFAULT 'Subscriber',
		    DROP COLUMN `permissions`,
		    ADD INDEX `pk_id_email` (`id`, `email`),
		    DROP INDEX `pk_id`;
		QUERY;
		$this->assertEquals( $query, self::$adapter->getQuery() );
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
		    `col_big_integer` BIGINT NOT NULL,
		    `col_binary` BLOB NOT NULL,
		    `col_boolean` TINYINT NOT NULL,
		    `col_char` CHAR(100) NOT NULL,
		    `col_date` DATE NOT NULL,
		    `col_datetime` DATETIME NOT NULL,
		    `col_decimal` DECIMAL(5, 2) NOT NULL,
		    `col_double` DOUBLE(15) NOT NULL,
		    `col_enum` ENUM NOT NULL,
		    `col_float` FLOAT NOT NULL,
		    `col_integer` INT NOT NULL,
		    `col_json` JSON NOT NULL,
		    `col_long_text` LONGTEXT NOT NULL,
		    `col_medium_integer` MEDIUMINT NOT NULL,
		    `col_medium_text` MEDIUMTEXT NOT NULL,
		    `col_small_integer` SMALLINT NOT NULL,
		    `col_tiny_integer` TINYINT NOT NULL,
		    `col_string` VARCHAR(100) NOT NULL,
		    `col_text` TEXT NOT NULL,
		    `col_time` TIME NOT NULL,
		    `col_timestamp` TIMESTAMP NOT NULL
		);
		QUERY;
		$this->assertEquals( $query, self::$adapter->getQuery() );
	}

	public function testDropKeys() {
		$schema = new Schema(self::$database);
		$schema->table('test', function(Table $table) {
			$table->dropKey('some_key');
			$table->dropUnique('uk_some_key');
			$table->dropPrimary('pk_some_key');
			$table->dropForeign('fk_some_key');
			$table->dropIndex('idx_some_key');
		});
		$query = <<<QUERY
		ALTER TABLE `test`
		    DROP INDEX `some_key`,
		    DROP INDEX `uk_some_key`,
		    DROP INDEX `pk_some_key`,
		    DROP INDEX `fk_some_key`,
		    DROP INDEX `idx_some_key`;
		QUERY;
		$this->assertEquals( $query, self::$adapter->getQuery() );
	}

	public function testRename() {
		$schema = new Schema(self::$database);
		$schema->rename('test', 'foo');
		$this->assertEquals( "RENAME TABLE `test` TO `foo`;", self::$adapter->getQuery() );
	}
}
