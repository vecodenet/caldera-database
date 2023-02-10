<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use Exception;

use PHPUnit\Framework\TestCase;

use Caldera\Database\Database;
use Caldera\Database\DatabaseException;
use Caldera\Database\Adapter\AdapterInterface;
use Caldera\Database\Adapter\MySQLAdapter;

class DatabaseWithMysqlAdapterTest extends TestCase {

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

	public function testConnectionFailure() {
		# Create database
		$options = [
			'host' => 'localhost',
			'name' => 'dummy',
			'user' => 'dummy',
		];
		try {
			self::$adapter = new MySQLAdapter($options);
			self::$database = new Database(self::$adapter);
			$this->fail('This must throw a DatabaseException');
		} catch (DatabaseException $e) {
			$this->assertInstanceOf(MySQLAdapter::class, $e->getAdapter());
		} catch (Exception $e) {
			$this->fail('The exception must be an instance of DatabaseException');
		}
	}

	public function testConnectionSucess() {
		# Make sure the database is connected
		$this->assertTrue( self::$database->isConnected() );
		$this->assertInstanceOf( MySQLAdapter::class, self::$database->getAdapter() );
		# Delete test table
		self::$database->query("DROP TABLE IF EXISTS test");
		# Create test table
		self::$database->query("CREATE TABLE test (id BIGINT NOT NULL AUTO_INCREMENT, name VARCHAR(100) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY pk_id (id))");
		# Get debug information
		$debug = self::$adapter->getDebugInfo();
		$this->assertIsArray($debug);
		$this->assertArrayHasKey('query', $debug);
		$this->assertArrayHasKey('parameters', $debug);
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
		self::$database->query("INSERT INTO test (id, name, created, updated) VALUES (0, 'Foo', NOW(), NOW()), (0, 'Bar', NOW(), NOW()), (0, 'Baz', NOW(), NOW())");
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

	public function testSelectSingle() {
		# Select a record
		$row = self::$database->first("SELECT id, name, created, updated FROM test WHERE id = ?", [2]);
		$this->assertIsObject($row);
		$this->assertEquals('TEST', $row->name);
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
