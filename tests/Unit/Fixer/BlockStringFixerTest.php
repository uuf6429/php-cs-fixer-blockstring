<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Fixer;

use InvalidArgumentException;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\GeneratedTokenCodec;
use uuf6429\PhpCsFixerBlockstringTests\Fixtures;

/**
 * @internal
 *
 * @phpstan-import-type TSerializedConfig from BlockStringFixer
 */
final class BlockStringFixerTest extends TestCase
{
	public function testIsRisky(): void
	{
		$this->assertTrue((new BlockStringFixer())->isRisky());
	}

	public function testGetNameMatches(): void
	{
		$this->assertSame(BlockStringFixer::NAME, (new BlockStringFixer())->getName());
	}

	public function testGetPriority(): void
	{
		$this->assertSame(0, (new BlockStringFixer())->getPriority());
	}

	public function testGetDefinition(): void
	{
		$this->expectNotToPerformAssertions();

		(new BlockStringFixer())->getDefinition();
	}

	public function testGetConfigurationDefinition(): void
	{
		$this->expectNotToPerformAssertions();

		(new BlockStringFixer())->getConfigurationDefinition();
	}

	/**
	 * @testWith ["not a serialized string"]
	 *           [[{"some": "invalid", "config": "structure"}]]
	 * @param mixed $formatters
	 */
	public function testConfigureWithInvalidConfiguration($formatters): void
	{
		$this->expectExceptionObject(new InvalidArgumentException('BlockStringFixer configuration is not valid.'));

		(new BlockStringFixer())->configure(['formatters' => $formatters]);
	}

	public function testConfig(): void
	{
		$this->assertSame(['formatters' => 'a:0:{}'], BlockStringFixer::config([]));
	}

	/**
	 * @param TSerializedConfig $config
	 * @dataProvider provideFixCases
	 */
	public function testApplyFix(array $config, string $input, string $expected): void
	{
		$fixer = new BlockStringFixer();
		$tokens = Tokens::fromCode($input);
		$fixer->configure($config);

		$fixer->fix(new SplFileInfo('fake.php'), $tokens);
		$output = $tokens->generateCode();

		$this->assertSame($expected, $output);
	}

	/**
	 * @return iterable<array-key, array{config: TSerializedConfig, input: string, expected: string}>
	 */
	public static function provideFixCases(): iterable
	{
		yield 'nowdoc with unregistered delimiter should be left unchanged' => [
			'config' => BlockStringFixer::config([]),
			'input' => <<<'PHP'
				<?php declare(strict_types=1);
				echo <<<'HTML'
					<h1>Hello world!</h1>
					HTML;
				PHP,
			'expected' => <<<'PHP'
				<?php declare(strict_types=1);
				echo <<<'HTML'
					<h1>Hello world!</h1>
					HTML;
				PHP,
		];

		yield 'nowdoc/heredoc html should have tags stripped out' => [
			'config' => BlockStringFixer::config(
				[
					'HTML' => new fixtures\Formatters\HtmlTagStripper(null),
				]
			),
			'input' => <<<'PHP'
				<?php declare(strict_types=1);
				echo <<<'HTML'
					<h1>Hello world1</h1>
					HTML;
				echo <<<"HTML"
					<h1>Hello world2</h1>
					HTML;
				echo <<<'XML'
					<h1>Hello world3</h1>
					XML;
				PHP,
			'expected' => <<<'PHP'
				<?php declare(strict_types=1);
				echo <<<'HTML'
					Hello world1
					HTML;
				echo <<<"HTML"
					Hello world2
					HTML;
				echo <<<'XML'
					<h1>Hello world3</h1>
					XML;
				PHP,
		];

		yield 'default formatter should apply to everything except other matching formatters' => [
			'config' => BlockStringFixer::config(
				[
					new fixtures\Formatters\TagWrapper('def'),
					'HTML' => new fixtures\Formatters\TagWrapper('htm'),
				]
			),
			'input' => <<<'PHP'
				<?php declare(strict_types=1);
				echo <<<'HTML'
					Hello world
					HTML;
				echo <<<'XML'
					Hello world
					XML;
				PHP,
			'expected' => <<<'PHP'
				<?php declare(strict_types=1);
				echo <<<'HTML'
					<htm>Hello world</htm>
					HTML;
				echo <<<'XML'
					<def>Hello world</def>
					XML;
				PHP,
		];

		yield 'heredoc with with a few variables' => [
			'config' => BlockStringFixer::config(
				[
					'HTML' => new fixtures\Formatters\HtmlTagStripper(new GeneratedTokenCodec()),
				]
			),
			'input' => <<<'PHP'
				<?php declare(strict_types=1);
				echo <<<"HTML"
					<h1 class="{$e['class']}">Hello $planet!</h1>
					HTML;
				PHP,
			'expected' => <<<'PHP'
				<?php declare(strict_types=1);
				echo <<<"HTML"
					Hello $planet!
					HTML;
				PHP,
		];

		yield 'Windows-style newlines' => [
			'config' => BlockStringFixer::config(
				[
					'HTML' => new fixtures\Formatters\HtmlTagStripper(null),
				]
			),
			'input' => "<?php\r\n\r\necho <<<'HTML'\r\n    <h1>Hello world!</h1>\r\n    HTML;\r\n",
			'expected' => "<?php\r\n\r\necho <<<'HTML'\r\n    Hello world!\r\n    HTML;\r\n",
		];
	}
}
