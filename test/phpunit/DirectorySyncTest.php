<?php
namespace Gt\Sync\Test;

use FilesystemIterator;
use Gt\Sync\DirectorySync;
use Gt\Sync\SyncException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DirectorySyncTest extends SyncTestCase {
	public function testSourceNotExists():void {
		self::expectException(SyncException::class);
		self::expectExceptionMessage("Source directory does not exist");
		new DirectorySync($this->getRandomTmp(), $this->getRandomTmp());
	}

	public function testDestinationNotExists():void {
		$source = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$dest = $this->getRandomTmp();
		$sut = new DirectorySync($source, $dest);

		self::assertDirectoryDoesNotExist($dest);
		$sut->exec();
		self::assertDirectoryExists($dest);
	}

	public function testCopy():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$this->createRandomFiles($source);

		$sut = new DirectorySync($source, $dest);
		$sut->exec();

		self::assertDirectoryContentsIdentical($source, $dest);
	}

	public function testCopyNewFileTouched():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$fileList = $this->createRandomFiles($source);

		$sut = new DirectorySync($source, $dest);
		$sut->exec();

		self::assertDirectoryContentsIdentical($source, $dest);

		$randomFile = $fileList[array_rand($fileList)];
		touch($randomFile, time() + 1);

		self::assertDirectoryContentsNotIdentical($source, $dest);
		$sut->exec();
		self::assertDirectoryContentsIdentical($source, $dest);
	}

	public function testCopyNewFileEditedNotCheckedByDefault():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$fileList = $this->createRandomFiles($source);

		$sut = new DirectorySync($source, $dest);
		$sut->exec();

		self::assertDirectoryContentsIdentical($source, $dest);

		$randomFile = $fileList[array_rand($fileList)];
		file_put_contents($randomFile, "UPDATED!!!", FILE_APPEND);

		self::assertDirectoryContentsNotIdentical($source, $dest);
		$sut->exec(DirectorySync::COMPARE_HASH);
		self::assertDirectoryContentsIdentical($source, $dest);
	}

	public function testDeleteFiles():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$numFiles = rand(10, 250);
		$this->createRandomFiles($source, $numFiles);
		$filesToDelete = [];
		$numFilesToDelete = rand(1, (int)round($numFiles / 5));
		for($i = 0; $i < $numFilesToDelete; $i++) {
			$f = $this->getRandomFileFromDirectory($source);
			if(!in_array($f, $filesToDelete)) {
				$filesToDelete []= $f;
			}
		}

		$sut = new DirectorySync($source, $dest);
		$sut->exec();
		self::assertDirectoryContentsIdentical($source, $dest);

		foreach($filesToDelete as $f) {
			self::assertFileExists($f);
			unlink($f);
			self::assertFileDoesNotExist($f);
		}

		$sut->exec();
		self::assertDirectoryContentsIdentical($source, $dest);
	}

	public function testDeleteDirectory():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$numFiles = rand(10, 250);
		$this->createRandomFiles($source, $numFiles);
		$dirToDelete = $this->getRandomSubdirectoryFromDirectory($source);

		$sut = new DirectorySync($source, $dest);
		$sut->exec();

		$this->recursiveDeleteDirectory($dirToDelete);

		$sut->exec();
		self::assertDirectoryContentsIdentical($source, $dest);
	}

	public function testCopyWrongConfig():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$sut = new DirectorySync($source, $dest);
		self::expectException(SyncException::class);
		self::expectExceptionMessage("Cannot compare both filemtime and hash");
		$sut->exec(
			DirectorySync::COMPARE_HASH
			| DirectorySync::COMPARE_FILEMTIME
		);
	}

	public function testGetCopiedFilesList():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$sourceFileList = $this->createRandomFiles($source);
		$sut = new DirectorySync($source, $dest);
		$sut->exec();

		$copiedFilesList = $sut->getCopiedFilesList();
		self::assertCount(count($sourceFileList), $copiedFilesList);

		$sut->exec();
		self::assertCount(0, $sut->getCopiedFilesList());
	}

	public function testGetDeletedFilesList():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$this->createRandomFiles($source);
		$sut = new DirectorySync($source, $dest);
		$sut->exec();

		$deletedFilesList = $sut->getDeletedFilesList();
		self::assertCount(0, $deletedFilesList);

		unlink($this->getRandomFileFromDirectory($source));
		$sut->exec();
		self::assertCount(1, $sut->getDeletedFilesList());
	}

	public function testGetSkippedFilesList():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$sourceFileList = $this->createRandomFiles($source);
		$sut = new DirectorySync($source, $dest);
		$sut->exec();

		$skippedFilesList = $sut->getSkippedFilesList();
		self::assertCount(0, $skippedFilesList);

		$sut->exec();
		$skippedFilesList = $sut->getSkippedFilesList();
		self::assertCount(count($sourceFileList), $skippedFilesList);
	}

	public function testCheck():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$this->createRandomFiles($source);

		$sut = new DirectorySync($source, $dest);
		self::assertFalse($sut->check());
		$sut->exec();
		self::assertTrue($sut->check());
	}

	public function testSetPattern():void {
		$source = $this->getRandomTmp();
		$dest = $this->getRandomTmp();
		mkdir($source, 0775, true);
		$this->createRandomFiles($source);

		$file1 = $this->getRandomFileFromDirectory($source);
		do {
			$file2 = $this->getRandomFileFromDirectory($source);
		}
		while($file2 === $file1);
		do {
			$file3 = $this->getRandomFileFromDirectory($source);
		}
		while($file3 === $file2);

// Rename three files to abcdef.file to abcdef.filematch
		rename($file1, $file1 . "match");
		rename($file2, $file2 . "match");
		rename($file3, $file3 . "match");

		$sut = new DirectorySync($source, $dest, "**/*.filematch");
		$sut->exec();
		$copiedFiles = $sut->getCopiedFilesList();
		self::assertCount(3, $copiedFiles);

		$sut->exec();
		$copiedFiles = $sut->getCopiedFilesList();
		self::assertCount(0, $copiedFiles);
	}
}
