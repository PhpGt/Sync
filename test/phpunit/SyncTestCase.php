<?php
namespace Gt\Sync\Test;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class SyncTestCase extends TestCase {
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

	protected function getRandomTmp():string {
		return implode(DIRECTORY_SEPARATOR, [
			$this->getBaseTempDirectory(),
			uniqid()
		]);
	}

	protected function getBaseTempDirectory():string {
		return implode(DIRECTORY_SEPARATOR, [
			sys_get_temp_dir(),
			"phpgt",
			"sync",
		]);
	}

	/** @return array<string> */
	protected function createRandomFiles(
		string $directory,
		int $numFiles = 100,
		int $randomNestLevel = 3
	):array {
		$fileList = [];

		for($i = 0; $i < $numFiles; $i++) {
			$subPathParts = [];
			$nestLevel = rand(0, $randomNestLevel);

			for($j = 0; $j <= $nestLevel; $j++) {
				$subPathParts []= uniqid();
			}

			$subPath = implode(DIRECTORY_SEPARATOR,
					$subPathParts
				) . ".file";

			$path = implode(DIRECTORY_SEPARATOR, [
				$directory,
				$subPath,
			]);

			if(!is_dir(dirname($path))) {
				mkdir(dirname($path), 0775, true);
			}
			file_put_contents($path, uniqid("content-"));
			array_push($fileList, $path);
		}

		return $fileList;
	}

	protected function getRandomFileFromDirectory(string $dir):string {
		do {
			$fileList = glob("$dir/*");
			$file = $fileList[array_rand($fileList)];
			if(is_dir($file)) {
				$dir = $file;
			}
			else {
				return $file;
			}
		}
		while($fileList);

		return "";
	}

	protected function getRandomSubdirectoryFromDirectory(string $dir):string {
		$fileList = glob("$dir/*");
		do {
			$file = $fileList[array_rand($fileList)];
		}
		while(!is_dir($file));

		return $file;
	}

	protected function recursiveDeleteDirectory(string $dir):void {
		$directory = new RecursiveDirectoryIterator(
			$dir,
			RecursiveDirectoryIterator::SKIP_DOTS
			| RecursiveDirectoryIterator::KEY_AS_PATHNAME
			| RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
		);
		$iterator = new RecursiveIteratorIterator(
			$directory,
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach($iterator as $pathName => $file) {
			/** @var $file SplFileInfo */
			if($file->getFilename() === "."
				|| $file->getFilename() === "..") {
				continue;
			}

			if(is_dir($pathName)) {
				rmdir($pathName);
			}
			else {
				unlink($pathName);
			}
		}

		if(is_dir($dir)) {
			rmdir($dir);
		}
	}

	protected static function assertDirectoryContentsIdentical(
		string $expectedPath,
		string $actualPath,
		bool $invertLogic = false
	):void {
		$totallyEqual = true;

		$directory = new RecursiveDirectoryIterator(
			$expectedPath,
			RecursiveDirectoryIterator::SKIP_DOTS
			| RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
			| RecursiveDirectoryIterator::KEY_AS_PATHNAME
		);
		$iterator = new RecursiveIteratorIterator(
			$directory,
			RecursiveIteratorIterator::CHILD_FIRST
		);
		$expectedFiles = iterator_to_array($iterator);

		$directory = new RecursiveDirectoryIterator(
			$expectedPath,
			RecursiveDirectoryIterator::SKIP_DOTS
			| RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
			| RecursiveDirectoryIterator::KEY_AS_PATHNAME
		);
		$iterator = new RecursiveIteratorIterator(
			$directory,
			RecursiveIteratorIterator::CHILD_FIRST
		);
		$actualFiles = iterator_to_array($iterator);

		foreach($expectedFiles as $expectedFilePath => $file) {
			/** @var SplFileInfo $file */
			$relativePath = substr(
				$expectedFilePath,
				strlen($expectedPath) + 1
			);

			$actualFilePath = implode(DIRECTORY_SEPARATOR, [
				$actualPath,
				$relativePath
			]);

			if(is_dir($expectedFilePath)) {
				if($invertLogic) {
					if(!is_dir($actualFilePath)) {
						$totallyEqual = false;
					}
				}
				else {
					self::assertDirectoryExists($actualFilePath);
				}
			}
			else {
				if($invertLogic) {
					$fileExists = file_exists($actualFilePath);
					$filemTimeEquals =
						filemtime($expectedFilePath)
						=== filemtime($actualFilePath);
					$md5Equals =
						md5_file($expectedFilePath)
						=== md5_file($actualFilePath);
					$equality =
						$fileExists
						&& $filemTimeEquals
						&& $md5Equals;

					if(!$equality) {
						$totallyEqual = false;
					}
				}
				else {
					self::assertFileExists($actualFilePath);
					self::assertEquals(
						filemtime($expectedFilePath),
						filemtime($actualFilePath),
						$actualFilePath
					);
					self::assertEquals(
						md5_file($expectedFilePath),
						md5_file($actualFilePath),
						$actualFilePath
					);
				}
			}
		}

// Asset deletions from source.
		foreach($actualFiles as $actualFilePath => $file) {
			/** @var SplFileInfo $file */
			$relativePath = substr(
				$actualFilePath,
				strlen($actualPath) + 1
			);

			$expectedFilePath = implode(DIRECTORY_SEPARATOR, [
				$expectedPath,
				$relativePath
			]);

			if(is_dir($actualFilePath)) {
				if($invertLogic) {
					if(!is_dir($expectedFilePath)) {
						$totallyEqual = false;
					}
				}
				else {
					self::assertDirectoryExists($expectedFilePath);
				}
			}
			else {
				if($invertLogic) {
					if(!is_file($expectedFilePath)) {
						$totallyEqual = false;
					}
				}
				else {
					self::assertFileExists($expectedFilePath);
				}
			}
		}

		if($invertLogic) {
			self::assertFalse($totallyEqual);
		}
	}

	protected static function assertDirectoryContentsNotIdentical(
		string $expectedPath,
		string $actualPath
	):void {
		self::assertDirectoryContentsIdentical(
			$expectedPath,
			$actualPath,
			true
		);
	}
}
