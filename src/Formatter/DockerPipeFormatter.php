<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

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
 *     stripLastNewLine: true,                     // Remove last line from docker output - typically needed.
 * ) ]]
 * ```
 *
 * @phpstan-type TDockerImageDetails array{platform: string, digest: string}
 */
class DockerPipeFormatter extends AbstractCodecFormatter
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
	 * @readonly
	 */
	private bool $stripLastNewLine;

	/**
	 * @param list<string> $options
	 * @param list<string> $command
	 * @param 'never'|'missing'|'always' $pullMode
	 */
	public function __construct(
		string          $image,
		array           $options = [],
		array           $command = [],
		string          $pullMode = 'never',
		?CodecInterface $interpolationCodec = null,
		bool            $stripLastNewLine = true
	) {
		$this->image = $image;
		$this->options = $options;
		$this->command = $command;
		$this->pullMode = $pullMode;
		$this->imageDetails = $this->resolveImageDetails();
		$this->stripLastNewLine = $stripLastNewLine;

		parent::__construct(
			"{$this->imageDetails['platform']};{$this->imageDetails['digest']}",
			$interpolationCodec
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

		$output = $process->mustRun()->getOutput();
		return ($this->stripLastNewLine && substr($output, -1) === "\n")
			? substr($output, 0, -1)
			: $output;
	}
}
