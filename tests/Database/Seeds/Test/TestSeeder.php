<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database\Seeds\Test;

use Caldera\Database\Seeds\AbstractSeeder;

class TestSeeder extends AbstractSeeder {

	/**
	 * Run seeder
	 * @return bool
	 */
	public function run(): bool {
		echo "Seeding";
		return true;
	}
}
