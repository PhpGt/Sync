<?php
namespace Gt\Sync\Command;

use Gt\Cli\Argument\ArgumentValueList;
use Gt\Cli\Argument\ArgumentValueListNotSetException;
use Gt\Cli\Command\Command;
use Gt\Cli\Parameter\NamedParameter;
use Gt\Cli\Parameter\Parameter;
use Gt\Sync\DirectorySync;

class SyncCommand extends Command {
	public function run(ArgumentValueList $arguments = null):void {
		$source = $arguments->get("source");
		$destination = $arguments->get("destination");
		try {
			$pattern = $arguments->get("pattern");
		}
		catch(ArgumentValueListNotSetException $exception) {
			$pattern = null;
		}

		$sync = new DirectorySync($source, $destination, $pattern);
		$sync->exec();

		if(!$arguments->contains("silent")) {
			$this->write("Copied ");
			$this->write(count($sync->getCopiedFilesList()));
			$this->write(", skipped ");
			$this->write(count($sync->getSkippedFilesList()));
			$this->write(", deleted ");
			$this->write(count($sync->getDeletedFilesList()));
			$this->writeLine(".");
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
}
