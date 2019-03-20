<?php
namespace Gt\Sync\Test;

use DirectoryIterator;
use FilesystemIterator;
use Gt\Sync\DirectorySync;
use Gt\Sync\SyncException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DirectorySyncTest extends TestCase {
	protected $tmp;

	public function setUp():void {
		$this->tmp = $this->getRandomTmp();
	}

	public function tearDown():void {
		$baseTmp = implode(DIRECTORY_SEPARATOR, [
			sys_get_temp_dir(),
			"phpgt",
			"sync",
		]);

		if(!is_dir($baseTmp)) {
			return;
		}

		$directory = new RecursiveDirectoryIterator(
			$baseTmp,
			FilesystemIterator::KEY_AS_PATHNAME
			| FilesystemIterator::CURRENT_AS_FILEINFO
		);
		$iterator = new RecursiveIteratorIterator(
			$directory,
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach($iterator as $filePath => $file) {
			/** @var $file SplFileInfo */
			if($file->getFilename() === "."
			|| $file->getFilename() === "..") {
				continue;
			}

			if($file->isDir()) {
				rmdir($filePath);
			}
			else {
				unlink($filePath);
			}
		}
	}

	public function testSourceNotExists() {
		self::expectException(SyncException::class);
		self::expectExceptionMessage("Source directory does not exist");
		new DirectorySync($this->tmp, $this->getRandomTmp());
	}

	public function testDestinationNotExists() {
		mkdir($this->tmp, 0775, true);
		$dest = $this->getRandomTmp();
		$sut = new DirectorySync($this->tmp, $dest);

		self::assertDirectoryNotExists($dest);
		$sut->exec();
		self::assertDirectoryExists($dest);
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