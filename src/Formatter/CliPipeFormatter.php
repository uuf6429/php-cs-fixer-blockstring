<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use Symfony\Component\Process\Process;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

/**
 * It's no secret that the best formatting tools are not directly available in PHP. This formatter off-loads formatting
 * to such external executables.
 *
 * Example:
 *
 * ```php
 * ['formatters' => [ new CliPipeFormatter(
 *     versionValueOrCommand: '1.0',               // Either a version as a string, or the command to get the version (as an array).
 *     formatCommand: ['cmd' => 'jfmt -'],         // An array defining the external command to do the formatting.
 *     interpolationCodec: new PlainStringCodec(), // A codec for handling interpolations; depends on the content being formatted.
 * ) ]]
 * ```
 *
 * The command definition (for version detection or formatting) is an array with the following structure:
 * - `cmd` - array/string - The command line e.g. `'jfmt --format'` or `['jfmt', '--format']`.
 * - `cwd` - (optional) string - The current working directory of the command.
 * - `env` - (optional) array of string keys and values - Environment variables to pass to the command.
 *
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
