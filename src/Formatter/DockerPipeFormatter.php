<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use InvalidArgumentException;
use RuntimeException;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\DefaultNormalizer;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\NormalizerInterface;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessFactoryInterface;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessFailedException;
use uuf6429\PhpCsFixerBlockstring\Process\SymfonyProcessFactory;

/**
 * The minimal setup, stable repeatability, and a rich ecosystem make Docker images an ideal source of formatting
 * tools. This formatter exists to take advantage of that.
 *
 * Example:
 *
 * ```php
 * return (new PhpCsFixer\Config())
 *     ->registerCustomFixers([new BlockStringFixer()])
 *     ->setRules([
 *         BlockStringFixer::NAME => BlockStringFixer::config([
 *             'JSON' => new DockerPipeFormatter(
 *                 // The docker image; might contain url, tag or even the digest.
 *                 image: 'ghcr.io/jqlang/jq',
 *                 // Optional docker arguments, such as for setting env vars.
 *                 options: ['-e', 'SOME_ENV=value'],
 *                 // The command to run within the container, including any arguments.
 *                 command: ['bin/tool', '--dry-run', '-'],
 *                 // How/when the image should be pulled: 'never', 'always' or 'missing'.
 *                 pullMode: 'always',
 *                 // A codec for handling interpolations; depends on the content being formatted.
 *                 interpolationCodec: new PlainStringCodec(),
 *                 // A normalizer for handling end-of-line characters.
 *                 lineEndingNormalizer: null,
 *                 // Factory for creating processes. Defaults to Symfony process factory.
 *                 processFactory = null,
 *             )
 *         ]),
 *     ]);
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
	 * @readonly
	 */
	private ProcessFactoryInterface $processFactory;

	/**
	 * @param list<string> $options
	 * @param list<string> $command
	 * @param 'never'|'missing'|'always' $pullMode
	 */
	public function __construct(
		string                   $image,
		array                    $options = [],
		array                    $command = [],
		string                   $pullMode = 'never',
		?CodecInterface          $interpolationCodec = null,
		?NormalizerInterface     $lineEndingNormalizer = null,
		?ProcessFactoryInterface $processFactory = null
	) {
		$this->image = $image;
		$this->options = $options;
		$this->command = $command;
		$this->pullMode = $pullMode;
		$this->processFactory = $processFactory ?? new SymfonyProcessFactory();
		$this->imageDetails = $this->resolveImageDetails();

		parent::__construct(
			sprintf(
				'%s: %s',
				static::class,
				implode(
					' ',
					array_merge(
						["{$this->imageDetails['platform']};{$this->imageDetails['digest']}"],
						$this->options,
						$this->command
					)
				)
			),
			$interpolationCodec,
			$lineEndingNormalizer ?? new DefaultNormalizer(DefaultNormalizer::LF, DefaultNormalizer::STRIP)
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
		$process = $this->processFactory->create(
			['docker', 'image', 'inspect', $this->image, '--format={{.Os}}/{{.Architecture}} {{.Id}}']
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
		$this->processFactory
			->create(['docker', 'image', 'pull', $this->image])
			->mustRun();
	}

	protected function formatContent(string $original): string
	{
		return $this->processFactory
			->create(
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
				$original
			)->mustRun()
			->getOutput();
	}
}
