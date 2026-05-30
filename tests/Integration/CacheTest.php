<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Integration;

use JsonException;
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
		copy(__DIR__ . '/../Fixtures/Scenarios/caching/input.php', self::$inputFile);
	}

	public static function tearDownAfterClass(): void
	{
		unlink(self::$inputFile);
		unlink(self::$cacheFile);
		rmdir(self::$workspace);
	}

	/**
	 * @testWith ["v1", "Fixed 1 of 1 files", "Cache file did not exist"]
	 *           ["v1", "Fixed 0 of 1 files", "Cache file existed already"]
	 *           ["v2", "Fixed 1 of 1 files", "Cache file existed already"]
	 *           ["v2", "Fixed 0 of 1 files", "Cache file existed already"]
	 *
	 * @throws JsonException
	 */
	public function testCacheReuse(
		string $formatterVersion,
		string $expectedProcessOutput,
		string $expectedCacheFileExistence
	): void {
		$cacheFileExistence = file_exists(self::$cacheFile)
			? 'Cache file existed already'
			: 'Cache file did not exist';
		$process = new Process(
			[
				PHP_BINARY,
				self::PCF_BINARY_PATH,
				'fix',
				'--config=' . __DIR__ . "/../Fixtures/Scenarios/caching/config-{$formatterVersion}.php",
				'--cache-file=' . self::$cacheFile,
				'--allow-unsupported-php-version=yes',
				'--show-progress=none',
				'--sequential',
				'-vvv',
				self::$inputFile,
			],
			self::$workspace,
			[
				'PHP_CS_FIXER_ALLOW_XDEBUG' => '1',
			]
		);

		$process->mustRun();
		$output = $process->getErrorOutput() . $process->getOutput();

		$this->assertSame($expectedCacheFileExistence, $cacheFileExistence);
		$this->assertFileEquals(__DIR__ . "/../Fixtures/Scenarios/caching/output-{$formatterVersion}.php", self::$inputFile);
		$this->assertStringContainsString($expectedProcessOutput, $output);
		$this->assertFileExists(self::$cacheFile);
		$this->assertStringContainsString("JsonCommenter (JsonCommenter {$formatterVersion})", (string)file_get_contents(self::$cacheFile));
	}
}
