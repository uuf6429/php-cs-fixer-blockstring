<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests;

use JsonException;
use PHPUnit\Framework\Assert;

/**
 * @phpstan-require-extends Assert
 */
trait CustomAssertionsTrait
{
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
