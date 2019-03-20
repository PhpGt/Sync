<?php
namespace Gt\Sync\Test;

use Gt\Sync\DirectorySync;
use Gt\Sync\SyncException;
use PHPUnit\Framework\TestCase;

class DirectorySyncTest extends TestCase {
	protected $tmp;

	public function setUp():void {
		$this->tmp = $this->getRandomTmp();
	}

	public function tearDown():void {
	}

	public function testSourceNotExists() {
		self::expectException(SyncException::class);
		self::expectExceptionMessage("Source directory does not exist");
		new DirectorySync($this->tmp, $this->getRandomTmp());
	}

	protected function getRandomTmp():string {
		return implode(DIRECTORY_SEPARATOR, [
			sys_get_temp_dir(),
			"phpgt",
			"sync",
			uniqid()
		]);
	}
}