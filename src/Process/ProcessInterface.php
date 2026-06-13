<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Process;

interface ProcessInterface
{
	/**
	 * @throws ProcessFailedException
	 */
	public function mustRun(): self;

	public function getOutput(): string;

	public function getErrorOutput(): string;
}
