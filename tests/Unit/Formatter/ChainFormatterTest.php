<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Formatter;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractFormatter;
use uuf6429\PhpCsFixerBlockstring\Formatter\ChainFormatter;

/**
 * @internal
 */
final class ChainFormatterTest extends TestCase
{
	public function testFormatBlock(): void
	{
		$formatter1 = $this->createMock(AbstractFormatter::class);
		$formatter1
			->expects($this->once())
			->method('formatBlock')
			->willReturnArgument(0);
		$formatter2 = $this->createMock(AbstractFormatter::class);
		$formatter2
			->expects($this->once())
			->method('formatBlock')
			->willReturnArgument(0);
		$chainFormatter = new ChainFormatter($formatter1, $formatter2);
		$inputBlockString = new BlockString('', '', [new StringSegment('foo')]);

		$outputBlockString = $chainFormatter->formatBlock($inputBlockString);

		$this->assertSame($inputBlockString, $outputBlockString);
	}
}
