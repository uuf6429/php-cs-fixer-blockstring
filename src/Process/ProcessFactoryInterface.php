<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Process;

interface ProcessFactoryInterface
{
	/**
	 * @param string|list<string> $command
	 * @param null|array<string, string> $env
	 */
	public function create(
		$command,
		?string $cwd = null,
		?array $env = null,
		?string $input = null
	): ProcessInterface;
}
