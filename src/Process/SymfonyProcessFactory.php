<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Process;

use Symfony\Component\Process\Process;

final class SymfonyProcessFactory implements ProcessFactoryInterface
{
	/**
	 * @readonly
	 */
	private ?float $timeout;

	public function __construct(?float $timeout = null)
	{
		$this->timeout = $timeout;
	}

	public function create(
		$command,
		?string $cwd = null,
		?array $env = null,
		?string $input = null
	): ProcessInterface {
		return new SymfonyProcess(
			is_array($command)
				? new Process($command, $cwd, $env, $input, $this->timeout)
				: Process::fromShellCommandline($command, $cwd, $env, $input, $this->timeout)
		);
	}
}
