<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\LineEndingNormalizer;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer\DefaultNormalizer;

/**
 * @internal
 *
 * @phpstan-import-type TChangeLinesTo from DefaultNormalizer
 * @phpstan-import-type TChangeFinalLineTo from DefaultNormalizer
 */
final class DefaultNormalizerTest extends TestCase
{
	/**
	 * @return iterable<array{
	 *     original: string,
	 *     formatted: string,
	 *     changeLinesTo: TChangeLinesTo,
	 *     changeFinalLineTo: TChangeFinalLineTo,
	 *     normalized: string,
	 * }>
	 */
	public static function provideNormalizerCases(): iterable
	{
		yield 'empty string' => [
			'original' => '',
			'formatted' => '',
			'changeLinesTo' => DefaultNormalizer::LF,
			'changeFinalLineTo' => DefaultNormalizer::NO_CHANGE,
			'normalized' => '',
		];

		yield 'single-line string' => [
			'original' => 'hello world!',
			'formatted' => 'hello world!',
			'changeLinesTo' => DefaultNormalizer::NO_CHANGE,
			'changeFinalLineTo' => DefaultNormalizer::NO_CHANGE,
			'normalized' => 'hello world!',
		];

		yield 'single-line string, ensure final eol' => [
			'original' => 'hello world!',
			'formatted' => 'hello world!',
			'changeLinesTo' => DefaultNormalizer::NO_CHANGE,
			'changeFinalLineTo' => DefaultNormalizer::ENSURE,
			'normalized' => 'hello world!',
		];

		yield 'unchanged' => [
			'original' => "hello\r\nworld\n!",
			'formatted' => "hello\r\nworld\n!",
			'changeLinesTo' => DefaultNormalizer::NO_CHANGE,
			'changeFinalLineTo' => DefaultNormalizer::NO_CHANGE,
			'normalized' => "hello\r\nworld\n!",
		];

		yield 'unix-style, ensure final eol' => [
			'original' => "hello\r\nworld\n!",
			'formatted' => "hello\r\nworld\n!",
			'changeLinesTo' => DefaultNormalizer::LF,
			'changeFinalLineTo' => DefaultNormalizer::ENSURE,
			'normalized' => "hello\nworld\n!\n",
		];

		yield 'windows-style, strip final eol' => [
			'original' => "hello\r\nworld\n!\n",
			'formatted' => "hello\r\nworld\n!\n",
			'changeLinesTo' => DefaultNormalizer::CRLF,
			'changeFinalLineTo' => DefaultNormalizer::ENSURE,
			'normalized' => "hello\r\nworld\r\n!\r\n",
		];

		yield 'auto, strip final eol' => [
			'original' => "hello\r\nworld\r\n!\r\n",
			'formatted' => "hello\nworld\n!\n",
			'changeLinesTo' => DefaultNormalizer::AUTO,
			'changeFinalLineTo' => DefaultNormalizer::STRIP,
			'normalized' => "hello\r\nworld\r\n!",
		];

		yield 'auto, add final eol' => [
			'original' => "hello\rworld\r!",
			'formatted' => "hello\nworld\n!\r\n",
			'changeLinesTo' => DefaultNormalizer::AUTO,
			'changeFinalLineTo' => DefaultNormalizer::ENSURE,
			'normalized' => "hello\rworld\r!\r",
		];

		yield 'single-line string, auto, add final eol' => [
			'original' => 'hello world!',
			'formatted' => "hello world!\n",
			'changeLinesTo' => DefaultNormalizer::AUTO,
			'changeFinalLineTo' => DefaultNormalizer::ENSURE,
			'normalized' => "hello world!\n",
		];

		yield 'unix eol, auto, add final eol' => [
			'original' => "hello world!\n",
			'formatted' => 'hello world!',
			'changeLinesTo' => DefaultNormalizer::AUTO,
			'changeFinalLineTo' => DefaultNormalizer::ENSURE,
			'normalized' => "hello world!\n",
		];

		yield 'single-line, windows eol, add final eol' => [
			'original' => 'hello world!',
			'formatted' => 'hello world!',
			'changeLinesTo' => DefaultNormalizer::CRLF,
			'changeFinalLineTo' => DefaultNormalizer::ENSURE,
			'normalized' => "hello world!\r\n",
		];
	}

	/**
	 * @param TChangeLinesTo $changeLinesTo
	 * @param TChangeFinalLineTo $changeFinalLineTo
	 *
	 * @dataProvider provideNormalizerCases
	 */
	public function testNormalize(
		string $original,
		string $formatted,
		string $changeLinesTo,
		string $changeFinalLineTo,
		string $normalized
	): void {
		$normalizer = new DefaultNormalizer($changeLinesTo, $changeFinalLineTo);

		$actual = $normalizer->normalize($formatted, $original);

		$this->assertSame($normalized, $actual);
	}
}
