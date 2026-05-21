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
		copy(__DIR__ . '/../fixtures/simple-input.php', self::$inputFile);
	}

	public static function tearDownAfterClass(): void
	{
		@unlink(self::$inputFile);
		@unlink(self::$cacheFile);
		@rmdir(self::$workspace);
	}

	/**
	 * @testWith ["Test v1", "Fixed 1 of 1 files", "Cache file did not exist"]
	 *           ["Test v1", "Fixed 0 of 1 files", "Cache file existed already"]
	 *           ["Test v2", "Fixed 1 of 1 files", "Cache file existed already"]
	 *           ["Test v2", "Fixed 0 of 1 files", "Cache file existed already"]
	 *
	 * @throws JsonException
	 */
	public function testCacheReuse(
		string $cacheFingerprint,
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
			['TEST_FORMATTER_CACHE_FINGERPRINT' => $cacheFingerprint]
		);

		$process->mustRun();

		$output = $process->getErrorOutput() . $process->getOutput();

		$this->assertSame($expectedCacheFileExistence, $cacheFileExistence);
		$this->assertFileEquals(__DIR__ . '/../fixtures/simple-output.php', self::$inputFile);
		$this->assertStringContainsString($expectedProcessOutput, $output);
		$this->assertJsonFileWithinJsonFile(
			__DIR__ . '/../fixtures/simple-cache.json',
			self::$cacheFile,
			['TEST_FORMATTER_CACHE_FINGERPRINT' => $cacheFingerprint]
		);
	}

	/**
	 * @param array<string, string> $placeholders
	 * @throws JsonException
	 */
	private function assertJsonFileWithinJsonFile(string $expectedFile, string $actualSubsetFile, array $placeholders = []): void
	{
		$this->assertFileExists($expectedFile);
		$this->assertFileExists($actualSubsetFile);
		$this->assertArraySubsetRecursive(
			(array)json_decode(strtr((string)file_get_contents($expectedFile), $placeholders), true, 512, JSON_THROW_ON_ERROR),
			(array)json_decode((string)file_get_contents($actualSubsetFile), true, 512, JSON_THROW_ON_ERROR),
			sprintf(
				'Failed asserting that %s contains %s',
				(string)realpath($actualSubsetFile),
				(string)realpath($expectedFile)
			)
		);
	}

	/**
	 * @param array<array-key, mixed> $expected
	 * @param array<array-key, mixed> $actual
	 */
	private function assertArraySubsetRecursive(array $expected, array $actual, string $message = '', string $path = '$'): void
	{
		foreach ($expected as $key => $value) {
			$this->assertArrayHasKey($key, $actual, ltrim("{$message}\nMissing key at path: {$path}"));

			if (!is_array($value) || !is_array($actual[$key])) {
				$this->assertSame($value, $actual[$key], ltrim("{$message}\nMismatch at path: {$path}"));
				continue;
			}

			$subPath = is_string($key) && preg_match('/^\w+$/', $key) === 1
				? "{$path}.{$key}"
				: "{$path}[" . var_export($key, true) . "]";
			$this->assertArraySubsetRecursive($value, $actual[$key], $message, $subPath);
		}
	}
}
