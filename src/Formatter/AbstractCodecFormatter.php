<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\PlainStringCodec;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\DefaultNormalizer;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\NormalizerInterface;

/**
 * This formatter base class is aware of string interpolation - it passes content through a codec before and after
 * formatting (to properly handle string interpolation).
 *
 * Additionally, it keeps an in-memory cache of formatted content to avoid unnecessary work within the same process.
 *
 * It can be used to embed any kind of formatter, including (native) PHP-based ones.
 *
 * Example with your own custom class:
 *
 * ```php
 * final class MyFormatter extends AbstractCodecFormatter
 * {
 *     protected function formatContent(string $original): string
 *     {
 *         return 'new content';
 *     }
 * }
 *
 * ['formatters' => [ new MyFormatter('1.0') ]]
 * ```
 *
 * Example with an anonymous class:
 *
 * ```php
 * ['formatters' => [
 *     new class ('1.0') extends AbstractCodecFormatter
 *     {
 *         protected function formatContent(string $original): string
 *         {
 *             return 'new content';
 *         }
 *     }
 * ]]
 * ```
 */
abstract class AbstractCodecFormatter extends AbstractFormatter
{
	/**
	 * @var array<string, string>
	 */
	private static array $cache = [];

	private static int $objectCounter = 0;

	private int $objectIndex;

	/**
	 * @readonly
	 */
	protected CodecInterface $interpolationCodec;

	/**
	 * @readonly
	 */
	private NormalizerInterface $lineEndingNormalizer;

	public function __construct(
		string               $version,
		?CodecInterface      $interpolationCodec=null,
		?NormalizerInterface $lineEndingNormalizer=null
	) {
		parent::__construct($version);

		$this->objectIndex = self::$objectCounter++;
		$this->interpolationCodec = $interpolationCodec ?? new PlainStringCodec();
		$this->lineEndingNormalizer = $lineEndingNormalizer ?? new DefaultNormalizer(DefaultNormalizer::NO_CHANGE, DefaultNormalizer::NO_CHANGE);
	}

	final public function formatBlock(BlockString $blockString): BlockString
	{
		$codecResult = $this->interpolationCodec->encode($blockString->segments);

		$cacheKey = $this->objectIndex . ':' . md5($codecResult->content);
		if (!isset(self::$cache[$cacheKey])) {
			$content = $this->removeIndentation($codecResult->content, $blockString->indentation);
			self::$cache[$cacheKey] = $this->lineEndingNormalizer->normalize(
				$this->formatContent($content),
				$content
			);
		}
		$newContent = $this->applyIndentation(self::$cache[$cacheKey], $blockString->indentation);

		$newSegments = $this->interpolationCodec->decode($codecResult->withContent($newContent));

		return $blockString->withSegments($newSegments);
	}

	/**
	 * Format the provided string accordingly and return a new one.
	 */
	abstract protected function formatContent(string $original): string;

	private function removeIndentation(string $lines, string $indentation): string
	{
		return substr(str_replace("\n{$indentation}", "\n", $lines), strlen($indentation));
	}

	private function applyIndentation(string $lines, string $indentation): string
	{
		return $indentation . str_replace("\n", "\n{$indentation}", $lines);
	}
}
