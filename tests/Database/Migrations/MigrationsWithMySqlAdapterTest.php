<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database\Migrations;

use RuntimeException;

use PHPUnit\Framework\TestCase;

use Caldera\Database\Database;
use Caldera\Database\Adapter\MySQLAdapter;
use Caldera\Database\Migrations\Migrations;
use Caldera\Database\Schema\Schema;

class MigrationsWithMySqlAdapterTest extends TestCase {

	/**
	 * Database adapter instance
	 * @var AdapterInterface
	 */
	protected static $adapter;

	/**
	 * Database instance
	 * @var Database
	 */
	protected static $database;

	protected function setUp(): void {
		# Create database
		$options = [
			'host' => getenv('TEST_DB_HOST') ?: 'localhost',
			'name' => getenv('TEST_DB_NAME') ?: 'caldera',
			'user' => getenv('TEST_DB_USER') ?: 'root',
		];
		self::$adapter = new MySQLAdapter($options);
		self::$database = new Database(self::$adapter);
	}

	public static function tearDownAfterClass(): void {
		$schema = new Schema(self::$database);
		$schema->dropIfExists('migration');
	}

	public function testSetup() {
		$migrations = new Migrations(self::$database);
		$migrations->setup();
		$schema = new Schema(self::$database);
		$this->assertTrue( $schema->hasTable('migration') );
	}

	public function testDummyPath() {
		$migrations = new Migrations(self::$database);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("The specified path does not exist");
		$migrations->path( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Dummy' );
	}

	public function testMigrate() {
		$migrations = new Migrations(self::$database);
		$migrations->path( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Test' );
		$migrations->migrate();
		$schema = new Schema(self::$database);
		$this->assertTrue( $schema->hasTable('test') );
		# Running it again shouldn't cause an error
		$migrations->migrate();
	}

	public function testStatus() {
		$migrations = new Migrations(self::$database);
		$migrations->path( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Test' );
		$status = $migrations->status();
		$check = (object) [
			'applied' => [
				'20221207_180842-CreateFileTable'
			],
			'available' => []
		];
		$this->assertEquals($check, $status);
	}

	public function testRollback() {
		$migrations = new Migrations(self::$database);
		$migrations->path( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Test' );
		$migrations->rollback(-1);
		$schema = new Schema(self::$database);
		$this->assertFalse( $schema->hasTable('test') );
		# Running it again shouldn't cause an error
		$migrations->rollback();
	}

	public function testReset() {
		$migrations = new Migrations(self::$database);
		$migrations->path( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Test' );
		$schema = new Schema(self::$database);
		$migrations->reset();
		$this->assertTrue( $schema->hasTable('test') );
		$migrations->rollback(1);
		$this->assertFalse( $schema->hasTable('test') );
	}
}
