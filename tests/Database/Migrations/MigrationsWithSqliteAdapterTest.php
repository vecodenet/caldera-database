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
use Caldera\Database\Adapter\SQLiteAdapter;
use Caldera\Database\Migrations\Migrations;
use Caldera\Database\Schema\Schema;

class MigrationsWithSqliteAdapterTest extends TestCase {

	/**
	 * Database path
	 * @var string
	 */
	protected static $path;

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

	public static function setUpBeforeClass(): void {
		self::$path = dirname( dirname(__DIR__) ) . '/output/migrations_test.sqlite';
		# Create an empty SQLite database file
		$data = '7dHBSsNAEAbg2WjxJClI8DpHBdvG3UvtRdO6SjCNmqxgb0YToWhtifHi0XfwAXw'.
				'jH8mkKPYQMHf/D35YZobZhY0vg2mR8f08nyUFK2qTEHTETCR6RGR9R5T5KLNOvw'.
				'T9qdzRjW82q2ObyH4gAAAAAAAAgH/gfdtxxNtGkdw+ZkX2XFSxRpH2jGbjDQPNV'.
				'YV3pin7odGnOuKLyB970YTP9ITDc8PhVRDs8VMyy9joa7NSu8uzpMhSPi63GX+s'.
				'V1ovi7S+tfvzMvvTPrQX+CEAAAAAAACAGj2rRY5SRsdGulJ23H5HHvB+fyDVQMq'.
				'aUnetRVtKDZPXZvPlHe5yPm+4Xyz3n8znzea/AA==';
		file_put_contents( self::$path, gzinflate( base64_decode($data) ) );
	}

	public static function tearDownAfterClass(): void {
		$schema = new Schema(self::$database);
		$schema->dropIfExists('migration');
	}

	protected function setUp(): void {
		# Create database
		$options = [
			'file' => self::$path
		];
		self::$adapter = new SQLiteAdapter($options);
		self::$database = new Database(self::$adapter);
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
