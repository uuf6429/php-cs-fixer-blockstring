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
	protected function setUp(): void
	{
		parent::setUp();

		if (PHP_OS_FAMILY === 'Windows' && getenv('GITHUB_ACTIONS') === 'true') {
			$this->markTestSkipped(
				'GitHub actions are not able to run non-Windows docker images: https://github.com/orgs/community/discussions/138554'
			);
		}
	}

	public function testFormat(): void
	{
		$formatter = new WslPipeFormatter(
			['cmd' => 'php -v'],
			['cmd' => ['php', '-r', 'echo strrev(stream_get_contents(STDIN));']]
		);
		$inputBlockString = new BlockString('', '', [new StringSegment('foobar')]);

		$outputBlockString = $formatter->formatBlock($inputBlockString);

		$this->assertSame('raboof', implode('', $outputBlockString->segments));
	}
}
