<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Formatter;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\Formatter\CliPipeFormatter;

/**
 * @internal
 */
final class CliPipeFormatterTest extends TestCase
{
	public function testFormat(): void
	{
		$formatter = new CliPipeFormatter(
			['cmd' => 'php -v'],
			['cmd' => ['php', '-r', 'echo "(" . stream_get_contents(STDIN) . ")";']]
		);
		$inputBlockString = new BlockString('', '', [new StringSegment('foo')]);

		$outputBlockString = $formatter->formatBlock($inputBlockString);

		$this->assertSame('(foo)', implode('', $outputBlockString->segments));
	}

	public function testFormatWithVersionOverride(): void
	{
		$formatter = new CliPipeFormatter(
			'some version',
			['cmd' => ['php', '-r', 'echo "(" . stream_get_contents(STDIN) . ")";']]
		);
		$inputBlockString = new BlockString('', '', [new StringSegment('foo')]);

		$outputBlockString = $formatter->formatBlock($inputBlockString);

		$this->assertSame('(foo)', implode('', $outputBlockString->segments));
	}
}
