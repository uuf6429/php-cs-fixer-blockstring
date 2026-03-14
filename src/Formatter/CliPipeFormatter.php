<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use Symfony\Component\Process\Process;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

/**
 * @phpstan-type TVersion string
 * @phpstan-type TCommand array{cmd: string|list<string>, cwd?: string, env?: array<string, string>}
 */
class CliPipeFormatter extends AbstractCodecFormatter
{
	/**
	 * @readonly
	 * @var TCommand
	 */
	private array $formatter;

	/**
	 * @param TVersion|TCommand $versionValueOrCommand Either the version (as a string) or a command to retrieve the
	 * version (as an array).
	 * @param TCommand $formatCommand A command, as an array, to perform the formatting.
	 */
	public function __construct(
		$versionValueOrCommand,
		array $formatCommand,
		?CodecInterface $interpolationCodec = null
	) {
		$this->formatter = $formatCommand;

		parent::__construct(
			is_string($versionValueOrCommand)
				? $versionValueOrCommand
				: $this->exec($versionValueOrCommand, null),
			$interpolationCodec
		);
	}

	/**
	 * @param TCommand $spec
	 * @return string
	 */
	private function exec(array $spec, ?string $input): string
	{
		$process = is_array($spec['cmd'])
			? new Process(
				$spec['cmd'],
				$spec['cwd'] ?? null,
				$spec['env'] ?? null,
				$input,
				null
			)
			: Process::fromShellCommandline(
				$spec['cmd'],
				$spec['cwd'] ?? null,
				$spec['env'] ?? null,
				$input,
				null
			);

		return $process->mustRun()->getOutput();
	}

	protected function formatContent(string $original): string
	{
		return $this->exec($this->formatter, $original);
	}
}
