<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Fixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;

/**
 * @internal
 *
 * @phpstan-import-type TFormatterConfig from BlockStringFixer
 */
abstract class AbstractFixerTestCase extends TestCase
{
	/**
	 * @return iterable<array-key, array{config: TFormatterConfig, input: string, expected: string}>
	 */
	abstract public static function provideFixCases(): iterable;

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
	 * @return class-string<FixerInterface>
	 */
	abstract protected function getFixerClass(): string;
}
