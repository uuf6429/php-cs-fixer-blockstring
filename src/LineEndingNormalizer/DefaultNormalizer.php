<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer;

class DefaultNormalizer implements NormalizerInterface
{
	public const NO_CHANGE = 'noop';
	public const ENSURE = 'ensure';
	public const STRIP = 'strip';
	public const LF = 'lf';
	public const CRLF = 'crlf';
	public const AUTO = 'auto';

	/**
	 * @var self::NO_CHANGE|self::LF|self::CRLF|self::AUTO
	 */
	private string $changeLinesTo;

	/**
	 * @var self::NO_CHANGE|self::ENSURE|self::STRIP
	 */
	private string $changeFinalLineTo;

	/**
	 * @param self::NO_CHANGE|self::LF|self::CRLF|self::AUTO $changeLinesTo
	 * @param self::NO_CHANGE|self::ENSURE|self::STRIP $changeFinalLineTo
	 */
	public function __construct(string $changeLinesTo, string $changeFinalLineTo)
	{
		$this->changeLinesTo = $changeLinesTo;
		$this->changeFinalLineTo = $changeFinalLineTo;
	}

	public function normalize(string $formatted, string $original): string
	{
		return $this->normalizeFinalLineEnding(
			$this->normalizeLineEnding($formatted, $original),
			$original
		);
	}

	private function normalizeLineEnding(string $text, string $original): string
	{
		switch ($this->changeLinesTo) {
			case self::LF:
				return $this->setLineEnding($text, "\n");

			case self::CRLF:
				return $this->setLineEnding($text, "\r\n");

			case self::AUTO:
				return $this->setLineEnding($text, $this->detectOriginalEol($original));

			case self::NO_CHANGE:
			default:
				return $text;
		}
	}

	private function setLineEnding(string $text, string $eol): string
	{
		if ($eol === '' || $text === '') {
			return $text;
		}

		return strtr($text, ["\r\n" => "\n", "\r" => "\n", "\n" => $eol]);
	}

	private function detectOriginalEol(string $text): string
	{
		$lfCount = substr_count($text, "\n");
		$crlfCount = substr_count($text, "\r\n");
		$crCount = substr_count($text, "\r");

		if ($crlfCount > $lfCount && $crlfCount > $crCount) {
			return "\r\n"; // Windows-style
		}
		if ($lfCount > $crCount) {
			return "\n"; // Unix-style
		}
		if ($crCount > 0) {
			return "\r"; // Mac-style
		}
		return '';
	}

	private function normalizeFinalLineEnding(string $text, string $original): string
	{
		switch ($this->changeFinalLineTo) {
			case self::ENSURE:
				return $this->appendFinalLineEnding($text, $original);

			case self::STRIP:
				return $this->removeFinalLineEnding($text);

			case self::NO_CHANGE:
			default:
				return $text;
		}
	}

	private function detectFinalLineEnding(string $text): string
	{
		$lastEol = substr($text, -2);
		if ($lastEol === "\r\n") {
			return $lastEol;
		}

		$lastEol = substr($lastEol, -1);
		if ($lastEol === "\r" || $lastEol === "\n") {
			return $lastEol;
		}

		return '';
	}

	private function appendFinalLineEnding(string $text, string $original): string
	{
		if ($this->detectFinalLineEnding($text) !== '') {
			return $text;
		}

		switch ($this->changeLinesTo) {
			case self::LF:
				return "$text\n";

			case self::CRLF:
				return "$text\r\n";

			case self::AUTO:
				return "$text{$this->detectOriginalEol($original)}";

			case self::NO_CHANGE:
			default:
				return $text;
		}
	}

	private function removeFinalLineEnding(string $text): string
	{
		return ($ending = $this->detectFinalLineEnding($text)) !== ''
			? substr($text, 0, -strlen($ending))
			: $text;
	}
}
