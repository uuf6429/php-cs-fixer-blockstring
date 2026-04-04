<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\Readmerator\Readmerator;

/**
 * @internal
 */
final class ReadmeratorTest extends TestCase
{
	public function testReadmeIsUpToDate(): void
	{
		ob_start();
		Readmerator::render();
		$actual = ob_get_clean();

		$this->assertNotFalse($actual);
		$this->assertStringEqualsFile(__DIR__ . '/../../README.md', $actual);
	}
}
