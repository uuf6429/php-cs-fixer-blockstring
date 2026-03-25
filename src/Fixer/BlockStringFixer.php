<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use uuf6429\PhpCsFixerBlockstring\BlockString\TokenStream;
use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractFormatter;
use const T_START_HEREDOC;

/**
 * @phpstan-type TFormatterConfig array{formatters: array<0|non-empty-string, AbstractFormatter>}
 *
 * @implements ConfigurableFixerInterface<TFormatterConfig, TFormatterConfig>
 */
final class BlockStringFixer extends AbstractFixer implements ConfigurableFixerInterface
{
	/**
	 * @use ConfigurableFixerTrait<TFormatterConfig, TFormatterConfig>
	 */
	use ConfigurableFixerTrait;

	/**
	 * @var null|TFormatterConfig
	 */
	protected ?array $configuration = null;

	public function isRisky(): bool
	{
		return true;
	}

	public function getName(): string
	{
		return 'Uuf6429/' . parent::getName();
	}

	public function getDefinition(): FixerDefinitionInterface
	{
		return new FixerDefinition(
			'A fixer that reformats HEREDOC/NOWDOC contents based on a delimiter match.',
			[],
		);
	}

	public function applyFix(SplFileInfo $file, Tokens $tokens): void
	{
		$blockStringStream = TokenStream::fromPhpCsFixerTokens($tokens);
		while ($blockString = $blockStringStream->next()) {
			$delimiter = $blockString->delimiter;
			$formatter = $this->configuration['formatters'][$delimiter] ?? $this->configuration['formatters'][0] ?? null;
			if ($formatter === null) {
				continue;
			}

			$blockStringStream->replace($formatter->formatBlock($blockString));
		}
	}

	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isTokenKindFound(T_START_HEREDOC);
	}

	protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
	{
		return new FixerConfigurationResolver([
			(new FixerOptionBuilder('formatters', 'A map of NOW/HEREDOC delimiters to FormatterInterface object pairs.'))
				->setAllowedTypes(['array'])
				->getOption(),
		]);
	}
}
