<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Formatter;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\Formatter\SimpleLineFormatter;

/**
 * @internal
 */
final class SimpleLineFormatterTest extends TestCase
{
	/**
	 * @return iterable<string, array{indentSize: int, indentChar: string, input: string, expected: string}>
	 */
	public static function provideFormatData(): iterable
	{
		yield 'no change' => [
			'indentSize' => 4,
			'indentChar' => ' ',
			'input' => 'foo',
			'expected' => 'foo',
		];

		yield 'trim trailing whitespace' => [
			'indentSize' => 4,
			'indentChar' => ' ',
			'input' => 'foo   ',
			'expected' => 'foo',
		];

		yield 'replace 4 spaces with 1 tab' => [
			'indentSize' => 4,
			'indentChar' => "\t",
			'input' => "    foo\n        bar\n",
			'expected' => "\tfoo\n\t\tbar\n",
		];

		yield 'replace 1 tab with 4 spaces' => [
			'indentSize' => 4,
			'indentChar' => " ",
			'input' => "\tfoo\n\t\tbar\n",
			'expected' => "    foo\n        bar\n",
		];

		yield 'replace 2 spaces with 1 tab' => [
			'indentSize' => 2,
			'indentChar' => "\t",
			'input' => "    foo\n        bar\n",
			'expected' => "\t\tfoo\n\t\t\t\tbar\n",
		];

		yield 'middle spaces should be ignored' => [
			'indentSize' => 4,
			'indentChar' => "\t",
			'input' => "    foo    bar    ",
			'expected' => "\tfoo    bar",
		];
	}

	/**
	 * @dataProvider provideFormatData
	 */
	public function testFormat(int $indentSize, string $indentChar, string $input, string $expected): void
	{
		// @phpstan-ignore argument.type, argument.type
		$formatter = new SimpleLineFormatter($indentSize, $indentChar);
		$inputBlockString = new BlockString('', '', [new StringSegment($input)]);

		$outputBlockString = $formatter->formatBlock($inputBlockString);

		$this->assertSame($expected, implode('', $outputBlockString->segments));
	}
}
