<?php
namespace Gt\Sync;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Webmozart\Glob\Glob;
use Webmozart\PathUtil\Path;

class DirectorySync extends AbstractSync {
	const COMPARE_FILEMTIME = 1<<0;
	const COMPARE_HASH = 2<<1;

	const DEFAULT_SETTINGS = self::COMPARE_FILEMTIME;

	/** @var array<string> */
	protected array $copiedFiles;
	/** @var array<string> */
	protected array $skippedFiles;
	/** @var array<string> */
	protected array $deletedFiles;

	public function __construct(
		protected string $source,
		protected string $destination,
		private string $glob = "**/*"
	) {
		$source = Path::makeAbsolute($source, getcwd());
		$destination = Path::makeAbsolute($destination, getcwd());

		parent::__construct($source, $destination);

		if(!is_dir($source)) {
			throw new SyncException("Source directory does not exist: $source");
		}
	}

	/**
	 * @param int $settings Bitmask of self::COMPARE_* values
	 * @return bool True if source and destination are in sync
	 */
	public function check(int $settings = self::DEFAULT_SETTINGS):bool {
		return $this->compareSourceDestination(
			".",
			$settings
		);
	}

	/**
	 * Performs the directory synchronisation
	 * @param int $settings Bitmask of self::COMPARE_* values
	 */
	public function exec(int $settings = self::DEFAULT_SETTINGS):void {
		$this->copiedFiles = [];
		$this->skippedFiles = [];
		$this->deletedFiles = [];

		$this->checkSettings($settings);

		$iteratorSettings = FilesystemIterator::KEY_AS_PATHNAME
			| FilesystemIterator::CURRENT_AS_FILEINFO;

		if(!is_dir($this->destination)) {
			mkdir($this->destination, 0775, true);
		}

		$sourceIterator = new RecursiveDirectoryIterator(
			$this->source,
			$iteratorSettings
		);
		$destinationIterator = new RecursiveDirectoryIterator(
			$this->destination,
			$iteratorSettings
		);

		$iterator = new RecursiveIteratorIterator(
			$destinationIterator,
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach($iterator as $pathName => $file) {
			/** @var $file SplFileInfo */
			if($file->getFilename() === "."
			|| $file->getFilename() === "..") {
				continue;
			}

			$pathName = Path::makeAbsolute($pathName, getcwd());

			$relativePath = substr(
				$pathName,
				strlen($this->destination) + 1
			);

			if(!$this->fileMatchesGlob($relativePath)) {
				continue;
			}

			if(!$this->sourceFileExists($relativePath)) {
				$this->delete($relativePath);
				array_push($this->deletedFiles, $relativePath);
			}
		}

		$iterator = new RecursiveIteratorIterator($sourceIterator);
		foreach($iterator as $pathName => $file) {
			/** @var $file SplFileInfo */
			if($file->getFilename() === "."
				|| $file->getFilename() === "..") {
				continue;
			}

			$pathName = Path::makeAbsolute($pathName, getcwd());
			$relativePath = substr(
				$pathName,
				strlen($this->source) + 1
			);

			$filesAreIdentical = $this->compareSourceDestination(
				$relativePath,
				$settings
			);

			if($filesAreIdentical
			|| !$this->fileMatchesGlob($relativePath)) {
				array_push($this->skippedFiles, $relativePath);
				continue;
			}

			$this->copy($relativePath);
			array_push($this->copiedFiles, $relativePath);
		}

		if(!empty($this->copiedFiles)
		|| !empty($this->deletedFiles)) {
			touch($this->destination, filemtime($this->source));
		}
	}

	/** @return array<string> */
	public function getCopiedFilesList():array {
		return $this->copiedFiles;
	}

	/** @return array<string> */
	public function getSkippedFilesList():array {
		return $this->skippedFiles;
	}

	/** @return array<string> */
	public function getDeletedFilesList():array {
		return $this->deletedFiles;
	}

	protected function copy(string $relativePath):void {
		$sourceFile = implode(DIRECTORY_SEPARATOR, [
			$this->source,
			$relativePath,
		]);
		$sourceFile = realpath($sourceFile);
		$destinationFile = implode(DIRECTORY_SEPARATOR, [
			$this->destination,
			$relativePath,
		]);

		if(!is_dir(dirname($destinationFile))) {
			mkdir(
				dirname($destinationFile),
				0775,
				true
			);
		}

		copy($sourceFile, $destinationFile);
		touch($destinationFile, filemtime($sourceFile));
	}

	protected function delete(string $relativePath):void {
		$destinationFile = implode(DIRECTORY_SEPARATOR, [
			$this->destination,
			$relativePath,
		]);
		$destinationFile = realpath($destinationFile);

		if(is_dir($destinationFile)) {
			rmdir($destinationFile);
		}
		else {
			unlink($destinationFile);
		}
	}

	protected function sourceFileExists(string $relativePath):bool {
		$sourceFile = implode(DIRECTORY_SEPARATOR, [
			$this->source,
			$relativePath,
		]);

		return file_exists($sourceFile);
	}

	protected function fileMatchesGlob(string $absolutePath):bool {
		$absoluteGlob = $this->source . "/" . $this->glob;
		$sourceFile = implode(DIRECTORY_SEPARATOR, [
			$this->source,
			$absolutePath
		]);

		$sourceFile = Path::makeAbsolute($sourceFile, getcwd());
		$sourceFile = Path::canonicalize($sourceFile);
		return Glob::match($sourceFile, $absoluteGlob);
	}

	protected function compareSourceDestination(
		string $relativePath,
		int $settings
	):bool {
		$comparatorFunction = null;

		if($settings & self::COMPARE_FILEMTIME) {
			$comparatorFunction = "filemtime";
		}
		if($settings & self::COMPARE_HASH) {
			$comparatorFunction = "md5_file";
		}

		$sourceFile = implode(DIRECTORY_SEPARATOR, [
			$this->source,
			$relativePath,
		]);
		$destinationFile = implode(DIRECTORY_SEPARATOR, [
			$this->destination,
			$relativePath,
		]);

		if(!file_exists($destinationFile)) {
			return false;
		}

		$sourceComp = $comparatorFunction($sourceFile);
		$destinationComp = $comparatorFunction($destinationFile);

		return $sourceComp === $destinationComp;
	}

	protected function checkSettings(int $settings):void {
		if($settings & self::COMPARE_FILEMTIME
		&& $settings & self::COMPARE_HASH) {
			throw new SyncException("Cannot compare both filemtime and hash.");
		}
	}
}
