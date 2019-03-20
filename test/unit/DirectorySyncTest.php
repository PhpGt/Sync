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
	public function tearDown():void {
		$baseTmp = $this->getBaseTempDirectory();

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
		new DirectorySync($this->getRandomTmp(), $this->getRandomTmp());
	}

	public function testDestinationNotExists() {
		$source = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$dest = $this->getRandomTmp();
		$sut = new DirectorySync($source, $dest);

		self::assertDirectoryNotExists($dest);
		$sut->exec();
		self::assertDirectoryExists($dest);
	}

	public function testCopy() {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$sut = new DirectorySync($source, $dest);
// TODO: Create some random files.
		$sut->exec();
	}

	protected function getBaseTempDirectory():string {
		return implode(DIRECTORY_SEPARATOR, [
			sys_get_temp_dir(),
			"phpgt",
			"sync",
		]);
	}

	protected function getRandomTmp():string {
		return implode(DIRECTORY_SEPARATOR, [
			$this->getBaseTempDirectory(),
			uniqid()
		]);
	}
}