<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Formatter;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\Formatter\WslPipeFormatter;
use uuf6429\PhpCsFixerBlockstringTests\MockProcessFactoryTrait;

/**
 * @internal
 */
final class WslPipeFormatterTest extends TestCase
{
	use MockProcessFactoryTrait;

	public function testFormat(): void
	{
		$formatter = new WslPipeFormatter(
			['cmd' => 'php -v'],
			['cmd' => ['php', '-r', 'echo strrev(stream_get_contents(STDIN));']],
			null,
			'login',
			null,
			$this->createProcessFactoryMock([
				[
					['wsl --shell-type login -- php -v', null, null, null],
					$this->createProcessMock('v1.0'),
				],
				[
					['wsl --shell-type login -- "php" "-r" "echo strrev(stream_get_contents(STDIN));"', null, null, 'foobar'],
					$this->createProcessMock('raboof'),
				],
				[
					['wsl --shell-type login -- \'php\' \'-r\' \'echo strrev(stream_get_contents(STDIN));\'', null, null, 'foobar'],
					$this->createProcessMock('raboof'),
				]
			])
		);
		$inputBlockString = new BlockString('', '', [new StringSegment('foobar')]);

		$outputBlockString = $formatter->formatBlock($inputBlockString);

		$this->assertSame('raboof', implode('', $outputBlockString->segments));
	}
}
