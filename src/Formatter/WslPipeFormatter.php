<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\DefaultNormalizer;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\NormalizerInterface;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessFactoryInterface;

/**
 * A formatter making use of Windows Subsystem for Linux (WSL). Of course, you will need to be running on Windows,
 * and WSL needs to be enabled and set up. Configuration is otherwise almost identical to {@see CliPipeFormatter}.
 */
class WslPipeFormatter extends CliPipeFormatter
{
	/**
	 * @readonly
	 * @var 'standard'|'login'|'none'
	 */
	private string $shellType;

	/**
	 * @param 'standard'|'login'|'none' $shellType
	 */
	public function __construct(
		$versionValueOrCommand,
		array $formatCommand,
		?CodecInterface $interpolationCodec = null,
		string $shellType = 'login',
		?NormalizerInterface $lineEndingNormalizer = null,
		?ProcessFactoryInterface $processFactory = null
	) {
		$this->shellType = $shellType;

		parent::__construct(
			$versionValueOrCommand,
			$formatCommand,
			$interpolationCodec,
			$lineEndingNormalizer ?? new DefaultNormalizer(DefaultNormalizer::LF, DefaultNormalizer::STRIP),
			$processFactory
		);
	}

	protected function exec(array $spec, ?string $input): string
	{
		$spec['cmd'] = sprintf(
			'wsl --shell-type %s -- %s',
			$this->shellType,
			is_string($spec['cmd'])
				? $spec['cmd']
				: implode(' ', array_map('escapeshellarg', $spec['cmd']))
		);

		return parent::exec($spec, $input);
	}
}
