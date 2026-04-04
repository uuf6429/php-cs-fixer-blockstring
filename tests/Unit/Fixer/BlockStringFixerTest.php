<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Fixer;

use InvalidArgumentException;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;
use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractCodecFormatter;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\GeneratedTokenCodec;

/**
 * @internal
 *
 * @phpstan-import-type TFormatterConfig from BlockStringFixer
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

	public function testConfigurationIsRequired(): void
	{
		$this->expectExceptionObject(
			new InvalidArgumentException('Configuration for fixer Uuf6429/block_string is required.')
		);

		(new BlockStringFixer())->fix(new SplFileInfo('test.php'), new Tokens());
	}

	/**
	 * @param TFormatterConfig $config
	 * @dataProvider provideFixCases
	 */
	final public function testApplyFix(array $config, string $input, string $expected): void
	{
		$fixer = new BlockStringFixer();
		$tokens = Tokens::fromCode($input);
		$fixer->configure($config);

		$fixer->fix(new SplFileInfo('fake.php'), $tokens);
		$output = $tokens->generateCode();

		$this->assertSame($expected, $output);
	}

	/**
	 * @return iterable<array-key, array{config: TFormatterConfig, input: string, expected: string}>
	 */
	public static function provideFixCases(): iterable
	{
		yield 'nowdoc with unregistered delimiter should be left unchanged' => [
			'config' => ['formatters' => []],
			'input' => <<<'PHP'
				<?php
				echo <<<'HTML'
					<h1>Hello world!</h1>
					HTML;
				PHP,
			'expected' => <<<'PHP'
				<?php
				echo <<<'HTML'
					<h1>Hello world!</h1>
					HTML;
				PHP,
		];

		yield 'nowdoc/heredoc html should have tags stripped out' => [
			'config' => [
				'formatters' => [
					'HTML' => new class extends AbstractCodecFormatter {
						public function __construct()
						{
							parent::__construct('1.0', null);
						}

						protected function formatContent(string $original): string
						{
							return strip_tags($original);
						}
					},
				],
			],
			'input' => <<<'PHP'
				<?php
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
				<?php
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
			'config' => [
				'formatters' => [
					new class extends AbstractCodecFormatter {
						public function __construct()
						{
							parent::__construct('1.0', null);
						}

						protected function formatContent(string $original): string
						{
							return "<def>$original</def>";
						}
					},
					'HTML' => new class extends AbstractCodecFormatter {
						public function __construct()
						{
							parent::__construct('1.0', null);
						}

						protected function formatContent(string $original): string
						{
							return "<htm>$original</htm>";
						}
					},
				],
			],
			'input' => <<<'PHP'
				<?php
				echo <<<'HTML'
					Hello world
					HTML;
				echo <<<'XML'
					Hello world
					XML;
				PHP,
			'expected' => <<<'PHP'
				<?php
				echo <<<'HTML'
					<htm>Hello world</htm>
					HTML;
				echo <<<'XML'
					<def>Hello world</def>
					XML;
				PHP,
		];

		yield 'heredoc with with a few variables' => [
			'config' => [
				'formatters' => [
					'HTML' => new class extends AbstractCodecFormatter {
						public function __construct()
						{
							parent::__construct('1.0', new GeneratedTokenCodec());
						}

						protected function formatContent(string $original): string
						{
							return strip_tags($original);
						}
					},
				],
			],
			'input' => <<<'PHP'
				<?php
				echo <<<"HTML"
					<h1 class="{$e['class']}">Hello $planet!</h1>
					HTML;
				PHP,
			'expected' => <<<'PHP'
				<?php
				echo <<<"HTML"
					Hello $planet!
					HTML;
				PHP,
		];
	}
}
