<?php
namespace Gt\Sync\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Argument\ArgumentValueListNotSetException;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;
use Gt\Sync\DirectorySync;
use Gt\Sync\SymlinkSync;

class SyncCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
		$source = $arguments->get("source");
		$destination = $arguments->get("destination");
		try {
			$pattern = $arguments->get("pattern");
		}
		catch(ArgumentValueListNotSetException) {
			$pattern = "**/*";
		}

		if($arguments->contains("symlink")) {
			$this->performSymlinkSync($arguments, $source, $destination);
		}
		else {
			$this->performDirectorySync($arguments, $source, $destination, $pattern);
		}
	}

	public function getName():string {
		return "sync";
	}

	public function getDescription():string {
		return "Synchronise two directories";
	}

	/** @return  NamedParameter[] */
	public function getRequiredNamedParameterList():array {
		return [
			new NamedParameter("source"),
			new NamedParameter("destination"),
		];
	}

	/** @return  NamedParameter[] */
	public function getOptionalNamedParameterList():array {
		return [];
	}

	/** @return  Parameter[] */
	public function getRequiredParameterList():array {
		return [];
	}

	/** @return  Parameter[] */
	public function getOptionalParameterList():array {
		return [
			new Parameter(
				true,
				"pattern",
				"p"
			),
			new Parameter(
				false,
				"symlink",
				"l",
			),
			new Parameter(
				false,
				"silent",
				"s"
			),
			new Parameter(
				false,
				"delete",
				"d"
			)
		];
	}

	private function performDirectorySync(
		ArgumentValueList $arguments,
		string $source,
		string $destination,
		string $pattern,
	):void {
		$sync = new DirectorySync($source, $destination, $pattern);
		$sync->exec();

		if(!$arguments->contains("silent")) {
			$this->write("Copied ");
			$this->write((string)count($sync->getCopiedFilesList()));
			$this->write(", skipped ");
			$this->write((string)count($sync->getSkippedFilesList()));
			$this->write(", deleted ");
			$this->write((string)count($sync->getDeletedFilesList()));
			$this->writeLine(".");
		}
	}

	private function performSymlinkSync(
		ArgumentValueList $arguments,
		string $source,
		string $destination,
	):void {
		$sync = new SymlinkSync($source, $destination);
		$sync->exec();

		$countDirectories = count($sync->getLinkedDirectoriesList());
		$countFiles = count($sync->getLinkedFilesList());
		$countSkipped = count($sync->getSkippedList());
		$countFailed = count($sync->getFailedList());

		if($countDirectories + $countFiles + $countFailed === 0
		&& $countSkipped > 0) {
			return;
		}

		if(!$arguments->contains("silent")) {
			$this->write("Linked: directories ");
			$this->write((string)$countDirectories);
			$this->write(", files ");
			$this->write((string)$countFiles);
			$this->write(", skipped ");
			$this->write((string)$countSkipped);
			$this->write(", failed ");
			$this->write((string)$countFailed);
			$this->writeLine(".");
		}
	}
}
