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
}
