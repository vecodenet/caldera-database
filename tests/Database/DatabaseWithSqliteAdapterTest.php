<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @version 1.0
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use Exception;

use PHPUnit\Framework\TestCase;

use Caldera\Database\Database;
use Caldera\Database\DatabaseException;
use Caldera\Database\Adapter\AdapterInterface;
use Caldera\Database\Adapter\SQLiteAdapter;

class DatabaseWithSqliteAdapterTest extends TestCase {

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
		self::$path = dirname(__DIR__) . '/output/database_test.sqlite';
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
		self::$adapter = null;
		self::$database = null;
	}

	protected function setUp(): void {
		# Create database
		$options = [
			'file' => self::$path
		];
		self::$adapter = new SQLiteAdapter($options);
		self::$database = new Database(self::$adapter);
	}

	public function testConnectionFailure() {
		# Create database
		$options = [];
		try {
			$adapter = new SQLiteAdapter($options);
			$database = new Database($adapter);
			$this->fail('This must throw a DatabaseException');
		} catch (DatabaseException $e) {
			$this->assertInstanceOf(SQLiteAdapter::class, $e->getAdapter());
		} catch (Exception $e) {
			$this->fail('The exception must be an instance of DatabaseException');
		}
	}

	public function testConnectionSucess() {
		$this->assertTrue( self::$database->isConnected() );
		$this->assertInstanceOf( SQLiteAdapter::class, self::$database->getAdapter() );
		# Delete test table
		self::$database->query("DROP TABLE IF EXISTS test");
		# Create test table
		self::$database->query("CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL)");
	}

	public function testErrorHandling() {
		# Test error handling
		try {
			self::$database->query("SELECT * FROM dummy");
			$this->fail('This must throw a DatabaseException');
		} catch (Exception $e) {
			$this->assertInstanceOf(DatabaseException::class, $e);
		}
	}

	public function testInsert() {
		# Insert some records
		self::$database->query("INSERT INTO test (name, created, updated) VALUES ('Foo', datetime('now'), datetime('now')), ('Bar', datetime('now'), datetime('now')), ('Baz', datetime('now'), datetime('now'))");
		$last = self::$database->lastInsertId();
		$this->assertNotEquals(0, $last);
	}

	public function testAutoTransactions() {
		# Update a record using transaction
		$result = self::$database->transaction(function($database) {
			self::$database->query("UPDATE test SET name = ? WHERE id = ?", ['TEST', 2]);
		});
		$this->assertTrue($result);
		# Fail an automatic transaction
		$result = self::$database->transaction(function($database) {
			throw new Exception('Fail transaction');
		});
		$this->assertFalse($result);
	}

	public function testManualTransactions() {
		# Manual transaction with commit
		self::$database->begin();
		self::$database->query("UPDATE test SET name = ? WHERE id = ?", ['Qux', 3]);
		self::$database->commit();
		# Manual transaction with rollback
		try {
			self::$database->begin();
			self::$database->query("UPDATE dummy SET name = ? WHERE id = ?", ['Cat', 3]);
		} catch (Exception $e) {
			self::$database->rollback();
			$this->assertInstanceOf(DatabaseException::class, $e);
		}
	}

	public function testSelect() {
		# Select a record
		$rows = self::$database->select("SELECT id, name, created, updated FROM test WHERE id = ?", [2]);
		$this->assertIsArray($rows);
		$this->assertEquals('TEST', $rows[0]->name);
	}

	public function testSelectScalar() {
		# Select a scalar
		$count = self::$database->scalar("SELECT count(*) FROM test");
		$this->assertIsNumeric($count);
		$this->assertEquals(3, $count);
		# Select a scalar on non-scalar query
		try {
			self::$database->scalar("SELECT * FROM test");
			$this->fail('This must throw a DatabaseException');
		} catch (Exception $e) {
			$this->assertInstanceOf(DatabaseException::class, $e);
		}
	}

	public function testChunkedSelect() {
		# Select chunked
		$chunks = self::$database->chunk(2, "SELECT id, name FROM test", [], function($rows) {
			$this->assertIsArray($rows);
		});
		$this->assertEquals(2, $chunks);
		# Select chunked stop
		$chunks = self::$database->chunk(2, "SELECT id, name FROM test", [], function($rows) {
			return false;
		});
		$this->assertEquals(1, $chunks);
	}
}
