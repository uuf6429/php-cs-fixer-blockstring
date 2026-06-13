<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Process;

use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process;

final class SymfonyProcess implements ProcessInterface
{
	/**
	 * @readonly
	 */
	private Process $process;

	public function __construct(Process $process)
	{
		$this->process = $process;
	}

	public function mustRun(): self
	{
		try {
			$this->process->mustRun();
		} catch (SymfonyProcessFailedException $e) {
			throw new ProcessFailedException("Process failed to run {$e->getMessage()}", 0, $e);
		}

		return $this;
	}

	public function getOutput(): string
	{
		return $this->process->getOutput();
	}

	public function getErrorOutput(): string
	{
		return $this->process->getErrorOutput();
	}
}
