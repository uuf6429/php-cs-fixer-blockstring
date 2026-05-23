<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Formatter;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractFormatter;

/**
 * @internal
 */
final class AbstractFormatterTest extends TestCase
{
	public function testThatAbstractFormatterIsCacheable(): void
	{
		$formatter = new class('fingerprint') extends AbstractFormatter {
			public function formatBlock(BlockString $blockString): BlockString
			{
				return $blockString;
			}
		};

		$this->assertSame('fingerprint', $formatter->getCacheFingerprint());
	}
}
