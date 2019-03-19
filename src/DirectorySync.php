<?php
namespace Gt\Sync;

use FilesystemIterator;
use RecursiveDirectoryIterator;

class DirectorySync extends AbstractSync {
	const COMPARE_FILEMTIME = 1<<0;
	const COMPARE_HASH = 2<<1;

	const DEFAULT_SETTINGS = self::COMPARE_FILEMTIME;

	public function __construct(string $source, string $destination) {
		parent::__construct($source, $destination);

		if(!is_dir($source)) {
			throw new SyncException("Source directory does not exist: $source");
		}
	}

	public function exec(int $settings = self::DEFAULT_SETTINGS):void {
		$this->checkSettings($settings);

		$directory = new RecursiveDirectoryIterator(
			$this->source,
			FilesystemIterator::SKIP_DOTS |
			FilesystemIterator::KEY_AS_PATHNAME |
			FilesystemIterator::CURRENT_AS_FILEINFO
		);

		foreach($directory as $file) {
			var_dump($file);die();
		}
	}

	protected function checkSettings(int $settings):void {
		if($settings & self::COMPARE_FILEMTIME
		&& $settings & self::COMPARE_HASH) {
			throw new SyncException("Can not compare both filemtime and hash.");
		}
	}
}