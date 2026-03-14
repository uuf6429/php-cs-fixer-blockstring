<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final class ExampleTest extends TestCase
{
	private const PCF_BINARY_PATH = __DIR__ . '/../../vendor/bin/php-cs-fixer';

	public function testExample(): void
	{
		$tempFile = tempnam(sys_get_temp_dir(), '');

		try {
			copy(__DIR__ . '/../fixtures/example-input.php', $tempFile);
			$process = new Process([
				'php',
				self::PCF_BINARY_PATH,
				'fix',
				'--using-cache=no',
				'--config=' . __DIR__ . '/../fixtures/example-config.php',
				'--sequential',
				'-vvv',
				'--diff',
				$tempFile,
			]);

			$process->mustRun();

			$this->assertFileEquals(__DIR__ . '/../fixtures/example-output.php', $tempFile);
		} finally {
			@unlink($tempFile);
		}
	}
}
