<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Fixer;

use InvalidArgumentException;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
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
final class BlockStringFixer implements FixerInterface, ConfigurableFixerInterface
{
	public const NAME = 'Uuf6429/block_string';

	private ?FixerConfigurationResolverInterface $configurationDefinition = null;

	/**
	 * @var null|TFormatterConfig
	 */
	private ?array $configuration = null;

	public function isRisky(): bool
	{
		return true;
	}

	public function getName(): string
	{
		return self::NAME;
	}

	public function getDefinition(): FixerDefinitionInterface
	{
		return new FixerDefinition(
			'A fixer that reformats HEREDOC/NOWDOC contents based on a delimiter match.',
			[],
		);
	}

	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isTokenKindFound(T_START_HEREDOC);
	}

	public function getPriority(): int
	{
		return 0;
	}

	public function supports(SplFileInfo $file): bool
	{
		return true;
	}

	public function getConfigurationDefinition(): FixerConfigurationResolverInterface
	{
		return $this->configurationDefinition
			?? $this->configurationDefinition = new FixerConfigurationResolver([
				(new FixerOptionBuilder('formatters', 'A map of NOW/HEREDOC delimiters to FormatterInterface object pairs.'))
					->setAllowedTypes(['array'])
					->getOption(),
			]);
	}

	public function configure(array $configuration): void
	{
		// @phpstan-ignore assign.propertyType
		$this->configuration = $this->getConfigurationDefinition()->resolve($configuration);
	}

	public function fix(SplFileInfo $file, Tokens $tokens): void
	{
		if ($this->configuration === null) {
			throw new InvalidArgumentException("Configuration for fixer {$this->getName()} is required.");
		}

		if (0 < $tokens->count() && $this->isCandidate($tokens) && $this->supports($file)) {
			$blockStringStream = TokenStream::fromPhpCsFixerTokens($tokens);
			while (($blockString = $blockStringStream->next()) !== null) {
				$delimiter = $blockString->delimiter;
				$formatter = $this->configuration['formatters'][$delimiter] ?? $this->configuration['formatters'][0] ?? null;
				if ($formatter === null) {
					continue;
				}

				$blockStringStream->replace($formatter->formatBlock($blockString));
			}
		}
	}
}
