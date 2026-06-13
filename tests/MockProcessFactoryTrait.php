<?php declare(strict_types=1);


namespace uuf6429\PhpCsFixerBlockstringTests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessFactoryInterface;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessFailedException;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessInterface;

/**
 * @internal
 * @phpstan-require-extends TestCase
 */
trait MockProcessFactoryTrait
{
	/**
	 * @param list<array{list<mixed>, ProcessInterface}> $processesToCreate
	 * @return ProcessFactoryInterface
	 */
	private function createProcessFactoryMock(array $processesToCreate): ProcessFactoryInterface
	{
		$processFactory = $this->createMock(ProcessFactoryInterface::class);
		$processFactory->method('create')
			->willReturnCallback(function () use ($processesToCreate) {
				$actualArgs = func_get_args();
				foreach ($processesToCreate as [$expectedArgs, $resultingProcess]) {
					if ($actualArgs === $expectedArgs) {
						return $resultingProcess;
					}
				}

				throw new RuntimeException(sprintf(
					'Unexpected call to ProcessFactory::create(%s)',
					implode(', ', array_map(static fn($arg) => var_export($arg, true), $actualArgs))
				));
			});

		return $processFactory;
	}

	private function createProcessMock(string $output = '', string $errorOutput = ''): ProcessInterface
	{
		$process = $this->createMock(ProcessInterface::class);
		$process->method('mustRun')->willReturnSelf();
		$process->method('getOutput')->willReturn($output);
		$process->method('getErrorOutput')->willReturn($errorOutput);

		return $process;
	}

	private function createFailingProcessMock(string $errorOutput = ''): ProcessInterface
	{
		$process = $this->createMock(ProcessInterface::class);
		$process->method('mustRun')->willThrowException(new ProcessFailedException('Process failed to run'));
		$process->method('getErrorOutput')->willReturn($errorOutput);

		return $process;
	}
}
