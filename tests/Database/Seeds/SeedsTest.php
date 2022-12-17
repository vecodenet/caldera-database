<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database\Seeds;

use PDOStatement;
use RuntimeException;

use PHPUnit\Framework\TestCase;

use Caldera\Database\Database;
use Caldera\Database\Seeds\Seeds;
use Caldera\Tests\Database\Seeds\Test\TestSeeder;
use Caldera\Tests\Database\TestMySqlAdapter;

class SchemaWithSqliteAdapterTest extends TestCase {

	public function testDummyPath() {
		/**
		 * PDOStatement mock
		 * @var Stub
		 */
		$mock = self::createStub(PDOStatement::class);
		$mock->method('fetchAll')->willReturn([]);
		$mock->method('fetch')->willReturn((object)[]);
		$adapter = new TestMySqlAdapter($mock);
		$database = new Database($adapter);
		$seeds = new Seeds($database);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("The specified path does not exist");
		$seeds->path( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Dummy' );
	}

	public function testSeeding() {
		/**
		 * PDOStatement mock
		 * @var Stub
		 */
		$mock = self::createStub(PDOStatement::class);
		$mock->method('fetchAll')->willReturn([]);
		$mock->method('fetch')->willReturn((object)[]);
		$adapter = new TestMySqlAdapter($mock);
		$database = new Database($adapter);
		$seeds = new Seeds($database);
		$seeds->path( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Test' );
		$this->expectOutputString("Seeding");
		$seeds->seed();
	}

	public function testSeedingByName() {
		/**
		 * PDOStatement mock
		 * @var Stub
		 */
		$mock = self::createStub(PDOStatement::class);
		$mock->method('fetchAll')->willReturn([]);
		$mock->method('fetch')->willReturn((object)[]);
		$adapter = new TestMySqlAdapter($mock);
		$database = new Database($adapter);
		$seeds = new Seeds($database);
		$seeds->path( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Test' );
		$this->expectOutputString("Seeding");
		$seeds->seed(TestSeeder::class);
	}
}