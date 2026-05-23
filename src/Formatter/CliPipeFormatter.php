<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use Symfony\Component\Process\Process;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\DefaultNormalizer;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\NormalizerInterface;

/**
 * It's no secret that the best formatting tools are not directly available in PHP. This formatter off-loads formatting
 * to such external executables.
 *
 * Example:
 *
 * ```php
 * return (new PhpCsFixer\Config())
 *     ->registerCustomFixers([new BlockStringFixer()])
 *     ->setRules([
 *         BlockStringFixer::NAME => BlockStringFixer::config([
 *             'J' => new CliPipeFormatter(
 *                 // Either a version as a string, or the command to get the version (as an array).
 *                 versionValueOrCommand: '1.0',
 *                 // An array defining the external command to do the formatting.
 *                 formatCommand: ['cmd' => 'jfmt -'],
 *                 // A codec for handling placeholers in template strings; depends on the content being formatted.
 *                 interpolationCodec: new PlainStringCodec(),
 *                 // A normalizer for handling end-of-line characters.
 *                 lineEndingNormalizer: null
 *             )
 *         ]),
 *     ]);
 * ```
 *
 * The command definition (for version detection or formatting) is an array with the following structure:
 *
 * - `cmd` - array/string - The command line e.g. `'jfmt --format'` or `['jfmt', '--format']`.
 * - `cwd` - (optional) string - The current working directory of the command.
 * - `env` - (optional) array of string keys and values - Environment variables to pass to the command.
 *
 * @phpstan-type TVersion string
 * @phpstan-type TCommand array{cmd: string|list<string>, cwd?: string, env?: array<string, string>}
 */
class CliPipeFormatter extends AbstractStringFormatter
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
	 * @param null|bool|NormalizerInterface $lineEndingNormalizer
	 */
	public function __construct(
		$versionValueOrCommand,
		array $formatCommand,
		?CodecInterface $interpolationCodec = null,
		$lineEndingNormalizer = false
	) {
		$this->formatter = $formatCommand;

		if (is_bool($lineEndingNormalizer)) {
			trigger_deprecation(
				'uuf6429/php-cs-fixer-blockstring',
				'1.0.4',
				'Passing a bool for argument $lineEndingNormalizer to %s is deprecated',
				__METHOD__
			);
			$lineEndingNormalizer = new DefaultNormalizer(
				DefaultNormalizer::LF,
				$lineEndingNormalizer ? DefaultNormalizer::STRIP : DefaultNormalizer::NO_CHANGE
			);
		}

		parent::__construct(
			sprintf(
				'%s: %s v%s',
				static::class,
				is_array($this->formatter['cmd'])
					? implode(' ', $this->formatter['cmd'])
					: $this->formatter['cmd'],
				is_string($versionValueOrCommand)
					? $versionValueOrCommand
					: $this->exec($versionValueOrCommand, null)
			),
			$interpolationCodec,
			$lineEndingNormalizer
		);
	}

	/**
	 * @param TCommand $spec
	 */
	protected function exec(array $spec, ?string $input): string
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
