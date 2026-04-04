<?php

namespace uuf6429\PhpCsFixerBlockstring\Readmerator;

use RuntimeException;

/**
 * @internal
 */
class Readmerator
{
	private const PROJECT_ROOT = __DIR__ . '/../../';
	private const README_TARGET = self::PROJECT_ROOT . 'README.md';
	private const README_TEMPLATE = __DIR__ . '/README.tpl.md';

	public static function rewrite(): void
	{
		file_put_contents(self::README_TARGET, (new self)->generate());
		echo "README.md generated\n";
	}

	public static function verify(): void
	{
		$expected = self::readFile(self::README_TARGET);
		$actual = (new self)->generate();

		if ($expected !== $actual) {
			throw new RuntimeException("README.md is not up-to-date:\n\n" . self::diff($expected, $actual));
		}
	}

	public function generate(): string
	{
		$projectRoot = self::normalizePath(self::PROJECT_ROOT);
		return strtr(
			self::readFile(self::README_TEMPLATE),
			[
				'{{PROJECT_NAME}}' => 'uuf6429/php-cs-fixer-blockstring',
				'{{EXAMPLE_CONFIG}}' => file_get_contents("$projectRoot/tests/fixtures/example-config.php"),
				'{{EXAMPLE_INPUT}}' => str_replace(
					[' ', "\t"],
					['·', '---→'],
					self::readFile("$projectRoot/tests/fixtures/example-input.php"),
				),
				'{{EXAMPLE_OUTPUT}}' => str_replace(
					[' ', "\t"],
					['·', '---→'],
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
			throw new RuntimeException("Could not normalize path: $path");
		}
		return $normalized;
	}

	private static function readFile(string $file): string
	{
		if (($content = file_get_contents($file)) === false) {
			throw new RuntimeException("Could not read file: $file");
		}
		return $content;
	}

	private static function diff(string $old, string $new): string
	{
		$oldLines = explode("\n", $old);
		$newLines = explode("\n", $new);

		$max = max(count($oldLines), count($newLines));
		$output = [];

		for ($i = 0; $i < $max; $i++) {
			$o = $oldLines[$i] ?? '';
			$n = $newLines[$i] ?? '';

			if ($o !== $n) {
				if ($o !== '') {
					$output[] = "- " . $o;
				}
				if ($n !== '') {
					$output[] = "+ " . $n;
				}
			}
		}

		return implode("\n", $output);
	}

	/**
	 * @return list<string>
	 */
	private static function findFiles(string $pattern): array
	{
		if (($files = glob($pattern)) === false) {
			throw new RuntimeException("Could not find files: $pattern");
		}
		return $files;
	}
}
