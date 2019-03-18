<?php
namespace Gt\Sync;

abstract class AbstractSync {
	protected $source;
	protected $destination;

	public function __construct(string $source, string $destination) {
		$this->source = $source;
		$this->destination = $destination;
	}

	abstract public function exec(string $mode = null):void;
}