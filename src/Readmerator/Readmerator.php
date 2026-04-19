<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Readmerator;

use RuntimeException;

/**
 * @internal
 */
final class Readmerator
{
	private const PROJECT_ROOT = __DIR__ . '/../../';
	private const README_TEMPLATE = __DIR__ . '/README.tpl.md';

	public static function render(): void
	{
		$projectRoot = self::normalizePath(self::PROJECT_ROOT);
		echo strtr(
			self::readFile(self::README_TEMPLATE),
			[
				'{{PROJECT_NAME}}' => 'uuf6429/php-cs-fixer-blockstring',
				'{{EXAMPLE_CONFIG}}' => file_get_contents("$projectRoot/tests/fixtures/example-config.php"),
				'{{EXAMPLE_INPUT}}' => str_replace(
					[' ', "\t"],
					['·', '───→'],
					self::readFile("$projectRoot/tests/fixtures/example-input.php"),
				),
				'{{EXAMPLE_OUTPUT}}' => str_replace(
					[' ', "\t"],
					['·', '───→'],
					self::readFile("$projectRoot/tests/fixtures/example-output.php")
				),
				'{{FORMATTERS}}' => rtrim(implode(
					"\n",
					array_map(
						static fn(string $classFile): string => sprintf(
							"### [%s](%s)\n\n%s\n",
							$className = basename($classFile, '.php'),
							str_replace($projectRoot, '.', $classFile),
							preg_match(
								"/\/\\*\\*(.*?)\\n(?: \\*\/| \* @)(.*?)\\n(abstract |final )?class $className/s",
								self::readFile($classFile),
								$match
							) === 1
								? trim((string)preg_replace(['/\n \* ?/', '/\{@see ([^}]+)}/'], ["\n", '[`$1`]'], $match[1]))
								: ''
						),
						self::findFiles("$projectRoot/src/Formatter/*.php")
					)
				), "\n"),
			]
		);
	}

	private static function normalizePath(string $path): string
	{
		if (($normalized = realpath($path)) === false) {
			throw new RuntimeException("Could not normalize path: $path"); // @codeCoverageIgnore
		}
		return $normalized;
	}

	private static function readFile(string $file): string
	{
		if (($content = file_get_contents($file)) === false) {
			throw new RuntimeException("Could not read file: $file"); // @codeCoverageIgnore
		}
		return $content;
	}

	/**
	 * @return list<string>
	 */
	private static function findFiles(string $pattern): array
	{
		if (($files = glob($pattern)) === false) {
			throw new RuntimeException("Could not find files: $pattern"); // @codeCoverageIgnore
		}

		usort($files, static fn(string $a, string $b) => str_replace('Formatter', '', $a) <=> str_replace('Formatter', '', $b));

		return $files;
	}
}
