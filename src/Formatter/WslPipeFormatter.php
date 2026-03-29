<?php

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

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
	 */
	public function __construct(
		$versionValueOrCommand,
		array $formatCommand,
		?CodecInterface $interpolationCodec = null,
		string $shellType = 'login',
		bool $stripLastNewLine = true
	) {
		$this->shellType = $shellType;

		parent::__construct($versionValueOrCommand, $formatCommand, $interpolationCodec, $stripLastNewLine);
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
