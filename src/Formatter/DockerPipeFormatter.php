<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\DefaultNormalizer;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\NormalizerInterface;

/**
 * The minimal setup, stable repeatability, and a rich ecosystem makes Docker images an ideal source of formatting
 * tools. This formatter exists to take advantage of that.
 *
 * Example:
 *
 * ```php
 * ['formatters' => [ new DockerPipeFormatter(
 *     image: 'ghcr.io/jqlang/jq',                 // The docker image; might contain url, tag or even the digest.
 *     options: ['-e', 'SOME_ENV=value'],          // Optional docker arguments, such as for setting env vars.
 *     command: ['bin/tool', '--dry-run', '-'],    // The command to run within the container, including any arguments.
 *     pullMode: 'always',                         // How/when the image should be pulled: 'never', 'always' or 'missing'.
 *     interpolationCodec: new PlainStringCodec(), // A codec for handling interpolations; depends on the content being formatted.
 *     lineEndingNormalizer: null,                 // A normalizer for handling end-of-line characters.
 * ) ]]
 * ```
 *
 * @phpstan-type TDockerImageDetails array{platform: string, digest: string}
 */
class DockerPipeFormatter extends AbstractStringFormatter
{
	/**
	 * @readonly
	 */
	private string $image;

	/**
	 * @readonly
	 * @var 'never'|'missing'|'always'
	 */
	private string $pullMode;

	/**
	 * @readonly
	 * @var list<string>
	 */
	private array $options;

	/**
	 * @readonly
	 * @var list<string>
	 */
	private array $command;

	/**
	 * @readonly
	 * @var TDockerImageDetails
	 */
	private array $imageDetails;

	/**
	 * @param list<string> $options
	 * @param list<string> $command
	 * @param 'never'|'missing'|'always' $pullMode
	 * @param null|bool|NormalizerInterface $lineEndingNormalizer
	 */
	public function __construct(
		string          $image,
		array           $options = [],
		array           $command = [],
		string          $pullMode = 'never',
		?CodecInterface $interpolationCodec = null,
		                $lineEndingNormalizer = true
	) {
		$this->image = $image;
		$this->options = $options;
		$this->command = $command;
		$this->pullMode = $pullMode;
		$this->imageDetails = $this->resolveImageDetails();

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
			"{$this->imageDetails['platform']};{$this->imageDetails['digest']}",
			$interpolationCodec,
			$lineEndingNormalizer
		);
	}

	/**
	 * @return TDockerImageDetails
	 */
	private function resolveImageDetails(): array
	{
		switch ($this->pullMode) {
			case 'never':
				return $this->inspectImage(true);

			case 'missing':
				// @codeCoverageIgnoreStart
				if (($result = $this->inspectImage(false)) !== null) {
					return $result;
				}
				$this->pullImage();
				return $this->inspectImage(true);
			// @codeCoverageIgnoreEnd

			case 'always':
				$this->pullImage();
				return $this->inspectImage(true);

			default:
				throw new InvalidArgumentException("Unsupported Pull Mode: {$this->pullMode}");
		}
	}

	/**
	 * @return ($throwOnFailure is true ? TDockerImageDetails : null|TDockerImageDetails)
	 */
	private function inspectImage(bool $throwOnFailure): ?array
	{
		$process = new Process(
			['docker', 'image', 'inspect', $this->image, '--format={{.Os}}/{{.Architecture}} {{.Id}}'],
			null,
			null,
			null,
			null
		);
		try {
			$result = $process->mustRun()->getOutput();
			$result = explode(' ', trim($result), 2);

			return ['platform' => $result[0], 'digest' => $result[1]];
		} catch (ProcessFailedException $ex) {
			if (!$throwOnFailure) {
				return null;
			}
			throw new RuntimeException(
				"Could not inspect docker image \"$this->image\":\n{$process->getErrorOutput()}"
			);
		}
	}

	private function pullImage(): void
	{
		(new Process(
			['docker', 'image', 'pull', $this->image],
			null,
			null,
			null,
			null
		))->mustRun();
	}

	protected function formatContent(string $original): string
	{
		$process = new Process(
			[
				'docker',
				'run',
				'--rm',
				'--interactive',
				...$this->options,
				$this->imageDetails['digest'],
				...$this->command,
			],
			null,
			null,
			$original,
			null
		);

		return $process->mustRun()->getOutput();
	}
}
