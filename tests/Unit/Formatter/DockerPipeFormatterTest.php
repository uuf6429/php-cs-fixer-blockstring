<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Formatter;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\Formatter\DockerPipeFormatter;

/**
 * @internal
 */
final class DockerPipeFormatterTest extends TestCase
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
		$formatter = new DockerPipeFormatter('ghcr.io/jqlang/jq', [], [], 'always');
		$inputBlockString = new BlockString('', '', [new StringSegment(
			"  {\"hello\"\n   : 	\"world\" , \"bye\":[  \"mars\" \n ]}"
		)]);

		$outputBlockString = $formatter->formatBlock($inputBlockString);

		$this->assertSame(
			<<<'JSON'
			{
			  "hello": "world",
			  "bye": [
			    "mars"
			  ]
			}
			
			JSON,
			implode('', $outputBlockString->segments)
		);
	}

	public function testBadPullMode(): void
	{
		$this->expectException(InvalidArgumentException::class);

		// @phpstan-ignore argument.type
		new DockerPipeFormatter('ghcr.io/jqlang/jq', [], [], 'bad');
	}

	public function testBadImageWithoutPulling(): void
	{
		$this->expectException(RuntimeException::class);

		new DockerPipeFormatter('docker.io/uuf6429/bad-image', [], [], 'never');
	}

	public function testBadImageWithMissingPulling(): void
	{
		$this->expectException(ProcessFailedException::class);

		new DockerPipeFormatter('docker.io/uuf6429/bad-image', [], [], 'missing');
	}
}
