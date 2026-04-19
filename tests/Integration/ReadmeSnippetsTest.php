<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReadmeSnippetsTest extends TestCase
{
	/**
	 * @var string
	 */
	private const README_FILE = __DIR__ . '/../../README.md';

	protected function setUp(): void
	{
		parent::setUp();

		if (PHP_OS_FAMILY === 'Windows' && getenv('GITHUB_ACTIONS') === 'true') {
			$this->markTestSkipped(
				'GitHub actions are not able to run non-Windows docker images: https://github.com/orgs/community/discussions/138554'
			);
		}
	}

	/**
	 * @requires PHP 8.0
	 * @dataProvider provideSnippets
	 */
	public function testSnippet(string $snippet): void
	{
		$snippet = <<<"PHP"
			use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractFormatter;
			use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractStringFormatter;
			use uuf6429\PhpCsFixerBlockstring\Formatter\DockerPipeFormatter;
			use uuf6429\PhpCsFixerBlockstring\Formatter\SimpleLineFormatter;
			use uuf6429\PhpCsFixerBlockstring\Formatter\CliPipeFormatter;
			use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\PlainStringCodec;
			
			$snippet;
			PHP;

		$this->expectNotToPerformAssertions();

		eval($snippet);
	}

	/**
	 * @return iterable<array{snippet: string}>
	 */
	public static function provideSnippets(): iterable
	{
		$content = file_get_contents(self::README_FILE);
		if ($content === false) {
			throw new \RuntimeException('File could not be read: ' . self::README_FILE);
		}
		$content = explode("\n## ⭐️ Formatters\n", $content, 2);

		preg_match_all('/\n```php\n(.+?)\n```\n/s', $content[1] ?? '', $matches);

		foreach ($matches[1] as $i => $match) {
			yield "Snippet #{$i}" => ['snippet' => $match];
		}
	}
}
