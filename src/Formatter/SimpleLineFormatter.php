<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

class SimpleLineFormatter extends AbstractCodecFormatter
{
	/**
	 * @readonly
	 * @var positive-int
	 */
	private int $indentSize;

	/**
	 * @readonly
	 * @var "\t"|' '
	 */
	private string $indentChar;

	/**
	 * @param positive-int $indentSize
	 * @param "\t"|' ' $indentChar
	 */
	public function __construct(
		int $indentSize = 4,
		string $indentChar = "\t",
		?CodecInterface $interpolationCodec = null
	) {
		parent::__construct('1', $interpolationCodec);

		$this->indentSize = $indentSize;
		$this->indentChar = $indentChar;
	}

	protected function formatContent(string $original): string
	{
		$eol = substr_count($original, "\r\n") > substr_count($original, "\n")
			? "\r\n" : "\n";

		return implode(
			$eol,
			array_map(
				function ($line) {
					$line = rtrim($line);

					$indentLength = strspn($line, " \t");
					$indent = substr($line, 0, $indentLength);
					$rest = substr($line, $indentLength);
					$width = 0;

					foreach (str_split($indent) as $ch) {
						$width += $ch === "\t" ? $this->indentSize : 1;
					}

					if ($this->indentChar === "\t") {
						$newIndent = str_repeat("\t", intdiv($width, $this->indentSize));
						$newIndent .= str_repeat(' ', $width % $this->indentSize);
					} else {
						$newIndent = str_repeat(' ', $width);
					}

					return $newIndent . $rest;
				},
				explode($eol, $original)
			)
		);
	}
}
