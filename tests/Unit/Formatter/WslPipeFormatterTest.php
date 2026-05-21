<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Formatter;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\Formatter\WslPipeFormatter;

/**
 * @internal
 */
final class WslPipeFormatterTest extends TestCase
{
	public function testFormat(): void
	{
		if (PHP_OS_FAMILY !== 'Windows') {
			$this->markTestSkipped('WSL is only available on Windows');
		}
		if (getenv('GITHUB_ACTIONS') === 'true') {
			$this->markTestSkipped('WSL on GitHub Actions is poorly supported and unusable');
		}

		$formatter = new WslPipeFormatter(
			['cmd' => 'php -v'],
			['cmd' => ['php', '-r', 'echo strrev(stream_get_contents(STDIN));']]
		);
		$inputBlockString = new BlockString('', '', [new StringSegment('foobar')]);

		$outputBlockString = $formatter->formatBlock($inputBlockString);

		$this->assertSame('raboof', implode('', $outputBlockString->segments));
	}
}
