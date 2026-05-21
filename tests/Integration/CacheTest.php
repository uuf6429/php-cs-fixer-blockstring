<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final class CacheTest extends TestCase
{
	private const PCF_BINARY_PATH = __DIR__ . '/../../vendor/bin/php-cs-fixer';

	private static string $workspace;
	private static string $inputFile;
	private static string $cacheFile;

	public static function setUpBeforeClass(): void
	{
		self::$workspace = sys_get_temp_dir() . '/pcf-cache-test-' . uniqid('', true);
		self::$inputFile = self::$workspace . '/example.php';
		self::$cacheFile = self::$workspace . '/cache.json';

		mkdir(self::$workspace);
		copy(__DIR__ . '/../fixtures/simple-input.php', self::$inputFile);
	}

	public static function tearDownAfterClass(): void
	{
		@unlink(self::$inputFile);
		@unlink(self::$cacheFile);
		@rmdir(self::$workspace);
	}

	/**
	 * @testWith ["v1.0", "Fixed 1 of 1 files", "Cache file did not exist"]
	 *           ["v1.0", "Fixed 0 of 1 files", "Cache file existed already"]
	 *           ["v1.1", "Fixed 1 of 1 files", "Cache file existed already"]
	 *           ["v1.1", "Fixed 0 of 1 files", "Cache file existed already"]
	 */
	public function testCacheReuse(
		string $fixerVersion,
		string $expectedProcessOutput,
		string $expectedCacheFileExistence
	): void {
		$cacheFileExistence = file_exists(self::$cacheFile)
			? 'Cache file existed already'
			: 'Cache file did not exist';
		$process = new Process(
			[
				'php',
				self::PCF_BINARY_PATH,
				'fix',
				'--config=' . __DIR__ . '/../fixtures/simple-config.php',
				'--cache-file=' . self::$cacheFile,
				'--allow-unsupported-php-version=yes',
				'--show-progress=none',
				'--sequential',
				'-vvv',
				self::$inputFile,
			],
			null,
			['TEST_FIXER_VERSION' => $fixerVersion]
		);

		$process->mustRun();

		$output = $process->getErrorOutput() . $process->getOutput();

		$this->assertSame($expectedCacheFileExistence, $cacheFileExistence);
		$this->assertFileEquals(__DIR__ . '/../fixtures/simple-output.php', self::$inputFile);
		$this->assertStringContainsString($expectedProcessOutput, $output);
	}
}
