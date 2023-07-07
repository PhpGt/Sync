<?php
namespace Gt\Sync\Test;
use Gt\Sync\SymlinkSync;

class SymlinkSyncTest extends SyncTestCase {
	public function testSymLink_directory():void {
		$baseDir = $this->getRandomTmp();
		$sourceDir = "$baseDir/data/upload";
		$destDir = "$baseDir/www/data/upload";

		$filePath = "$sourceDir/subdir1/subdir2/example.file";
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), recursive: true);
		}
		file_put_contents($filePath, "Hello, Sync!");

		$sut = new SymlinkSync($sourceDir, $destDir);
		$sut->exec();
		$linkedFiles = $sut->getLinkedFilesList();
		self::assertCount(0, $linkedFiles);
		$linkedDirs = $sut->getLinkedDirectoriesList();
		self::assertCount(1, $linkedDirs);
		$linkedAll = $sut->getCombinedLinkedList();
		self::assertCount(1, $linkedAll);
	}

	public function testSymLink_file():void {
		$baseDir = $this->getRandomTmp();
		$sourceFile = "$baseDir/data/something.txt";
		$destFile = "$baseDir/www/data/something.txt";

		if(!is_dir(dirname($sourceFile))) {
			mkdir(dirname($sourceFile), recursive: true);
		}
		file_put_contents($sourceFile, "Hello, Sync!");

		$sut = new SymlinkSync($sourceFile, $destFile);
		$sut->exec();
		$linkedFiles = $sut->getLinkedFilesList();
		self::assertCount(1, $linkedFiles);
		$linkedDirs = $sut->getLinkedDirectoriesList();
		self::assertCount(0, $linkedDirs);
		$linkedAll = $sut->getCombinedLinkedList();
		self::assertCount(1, $linkedAll);
	}

	public function testSymLink_changeDirPath():void {
		$baseDir = $this->getRandomTmp();
		$sourceDirOld = "$baseDir/data/upload-old";
		$sourceDirNew = "$baseDir/data/upload-new";
		$destDir = "$baseDir/www/data/upload";

		$relativeFilePath = "subdir1/subdir2/example.file";
		$filePath = "$sourceDirOld/$relativeFilePath";
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), recursive: true);
		}
		file_put_contents($filePath, "Hello, Sync!");

		$sut = new SymlinkSync($sourceDirOld, $destDir);
		$sut->exec();

		self::assertFileExists("$destDir/$relativeFilePath");
		rename($sourceDirOld, $sourceDirNew);
		self::assertFileDoesNotExist("$destDir/$relativeFilePath");

		$sut = new SymlinkSync($sourceDirNew, $destDir);
		$sut->exec();
		self::assertFileExists("$destDir/$relativeFilePath");
	}

	public function testSymLink_multipleCallsToExec():void {
		$baseDir = $this->getRandomTmp();
		$sourceDir = "$baseDir/data/upload";
		$destDir = "$baseDir/www/data/upload";

		$filePath = "$sourceDir/subdir1/subdir2/example.file";
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), recursive: true);
		}
		file_put_contents($filePath, "Hello, Sync!");

		$sut = new SymlinkSync($sourceDir, $destDir);
		$sut->exec();
		self::assertCount(1, $sut->getCombinedLinkedList());
		self::assertCount(0, $sut->getFailedList());
		self::assertCount(0, $sut->getSkippedList());
		$sut->exec();
		self::assertCount(0, $sut->getLinkedFilesList());
		self::assertCount(0, $sut->getFailedList());
		self::assertCount(1, $sut->getSkippedList());
	}
}
