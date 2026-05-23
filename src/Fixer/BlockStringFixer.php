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
 * @phpstan-type TFormatters array<0|non-empty-string, AbstractFormatter>
 * @phpstan-type TDeserilizedConfig array{formatters: TFormatters}
 * @phpstan-type TSerializedConfig array{formatters: string}
 *
 * @implements ConfigurableFixerInterface<TSerializedConfig, TDeserilizedConfig>
 */
final class BlockStringFixer implements FixerInterface, ConfigurableFixerInterface
{
	public const NAME = 'Uuf6429/block_string';

	private ?FixerConfigurationResolverInterface $configurationDefinition = null;

	/**
	 * @var TDeserilizedConfig
	 */
	private array $configuration;

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

	/**
	 * @param TDeserilizedConfig $configuration
	 * @return void
	 */
	public function configure(array $configuration): void
	{
		if (isset($configuration['formatters']) && !is_string($configuration['formatters'])) {
			throw new InvalidArgumentException(
				'BlockStringFixer configuration is not valid. '
				. 'Did you set it up in your PHP CS Fixer config with `BlockStringFixer::config()`?'
			);
		}

		$this->configuration = [
			'formatters' => unserialize($configuration['formatters'] ?? 'a:0:{}', ['allowed_classes' => true]),
		];
	}

	public function fix(SplFileInfo $file, Tokens $tokens): void
	{
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

	/**
	 * @param TFormatters $formatters
	 * @return TSerializedConfig
	 */
	public static function config(array $formatters): array
	{
		return [
			'formatters' => serialize($formatters),
		];
	}
}
