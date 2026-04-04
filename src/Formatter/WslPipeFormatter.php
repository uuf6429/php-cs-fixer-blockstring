<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\DefaultNormalizer;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\NormalizerInterface;

/**
 * A formatter making use of Windows Subsystem for Linux (WSL). Of course you will need to be running on Windows and WSL
 * needs to be enabled and set up. Configuration is otherwise almost identical to {@see CliPipeFormatter}.
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
	 * @param null|bool|NormalizerInterface $lineEndingNormalizer
	 */
	public function __construct(
		$versionValueOrCommand,
		array $formatCommand,
		?CodecInterface $interpolationCodec = null,
		string $shellType = 'login',
		$lineEndingNormalizer = true
	) {
		$this->shellType = $shellType;

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

		parent::__construct($versionValueOrCommand, $formatCommand, $interpolationCodec, $lineEndingNormalizer);
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
