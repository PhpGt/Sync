<?php
namespace Gt\Sync;

class SymlinkSync extends AbstractSync {
	/** @var array<string> */
	protected array $linkedFiles;
	/** @var array<string> */
	protected array $linkedDirectories;
	/** @var array<string> */
	protected array $skipped;
	/** @var array<string> */
	protected array $failed;

	// phpcs:ignore
	public function exec(int $settings = 0):void {
		$this->linkedFiles = [];
		$this->linkedDirectories = [];
		$this->skipped = [];
		$this->failed = [];

		$targetSource = realpath($this->source);

		if(is_link($this->destination)) {
			$linkTarget = readlink($this->destination);

			if($targetSource !== $linkTarget) {
				unlink($this->destination);
			}
			else {
				array_push($this->skipped, $this->destination);
				return;
			}
		}

		if(is_dir($this->source)) {
			if(!is_dir(dirname($this->destination))) {
				mkdir(dirname($this->destination), recursive: true);
			}

			if(symlink($targetSource, $this->destination)) {
				array_push($this->linkedDirectories, $this->destination);
			}
			else {
				array_push($this->failed, $this->destination);
			}
		}
		elseif(is_file($this->source)) {
			if(!is_dir(dirname($this->destination))) {
				mkdir(dirname($this->destination), recursive: true);
			}

			if(symlink($targetSource, $this->destination)) {
				array_push($this->linkedFiles, $this->destination);
			}
			else {
				array_push($this->failed, $this->destination);
			}
		}
	}

	/** @return array<string> */
	public function getLinkedFilesList():array {
		return $this->linkedFiles;
	}

	/** @return array<string> */
	public function getLinkedDirectoriesList():array {
		return $this->linkedDirectories;
	}

	/** @return array<string> */
	public function getCombinedLinkedList():array {
		return array_merge(
			$this->getLinkedFilesList(),
			$this->getLinkedDirectoriesList(),
		);
	}

	/** @return array<string> */
	public function getFailedList():array {
		return $this->failed;
	}

	/** @return array<string> */
	public function getSkippedList():array {
		return $this->skipped;
	}
}
