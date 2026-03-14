<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

/**
 * A formatter that normalizes indentation and removes any trailing whitespace at the end of lines.
 *
 * Example:
 *
 * ```php
 * ['formatters' => [ new SimpleLineFormatter(
 *     indentSize: 4,                              // The number of spaces defining one indentation level in your project.
 *     indentChar: "\t",                           // The actual character used for indentation (space or tab).
 *     interpolationCodec: new PlainStringCodec(), // A codec for handling interpolations; depends on the content being formatted.
 * ) ]]
 * ```
 */
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
		int             $indentSize = 4,
		string          $indentChar = "\t",
		?CodecInterface $interpolationCodec = null
	) {
		parent::__construct('1', $interpolationCodec);

		$this->indentSize = $indentSize;
		$this->indentChar = $indentChar;
	}

	protected function formatContent(string $original): string
	{
		$eol = (substr_count($original, "\r\n") >= substr_count($original, "\n"))
			? "\r\n" : "\n";

		return implode(
			$eol,
			array_map(
				function (string $line): string {
					$line = rtrim($line);
					$indentLength = strspn($line, " \t");
					$indent = substr($line, 0, $indentLength);
					$rest = substr($line, $indentLength);
					$width = 0;

					$width += substr_count($indent, ' ');
					$width += substr_count($indent, "\t") * $this->indentSize;

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
