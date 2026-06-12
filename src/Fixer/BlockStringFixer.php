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
 * @phpstan-type TDeserializedConfig array{formatters: TFormatters}
 * @phpstan-type TSerializedConfig array{formatters?: mixed}
 *
 * @implements ConfigurableFixerInterface<TSerializedConfig, TDeserializedConfig>
 */
final class BlockStringFixer implements FixerInterface, ConfigurableFixerInterface
{
	public const NAME = 'Uuf6429/block_string';

	private ?FixerConfigurationResolverInterface $configurationDefinition = null;

	/**
	 * @var TDeserializedConfig
	 */
	private array $configuration = [
		'formatters' => [],
	];

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
		$formatters = $configuration['formatters'] ?? [];
		if (!is_array($formatters)) {
			throw new InvalidArgumentException(sprintf(
				'BlockStringFixer configuration is not valid: formatters must be an array, "%s" given.',
				is_object($formatters) ? get_class($formatters) : gettype($formatters)
			));
		}
		foreach ($formatters as $key => $formatter) {
			if (is_int($key) && $key !== 0) {
				throw new InvalidArgumentException(sprintf(
					'BlockStringFixer configuration is not valid: formatter for integer key %s will never be used.',
					$key
				));
			}

			if (!$formatter instanceof AbstractFormatter) {
				throw new InvalidArgumentException(sprintf(
					'BlockStringFixer configuration is not valid: formatter for key %s must be an instance of %s, %s was given instead.',
					$key,
					AbstractFormatter::class,
					is_object($formatter) ? get_class($formatter) : gettype($formatter)
				));
			}
		}
		$this->configuration['formatters'] = $formatters;
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
			'formatters' => $formatters,
		];
	}
}
