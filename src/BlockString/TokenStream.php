<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\BlockString;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens as PhpCsFixerTokens;
use RuntimeException;

final class TokenStream
{
	/**
	 * @readonly
	 */
	private PhpCsFixerTokens $tokens;

	private int $currentIndex = 0;
	private int $expectedReplacements = 0;

	private function __construct(PhpCsFixerTokens $tokens)
	{
		$this->tokens = $tokens;
	}

	public static function fromPhpCsFixerTokens(PhpCsFixerTokens $tokens): self
	{
		return new self($tokens);
	}

	public function next(): ?BlockString
	{
		$this->expectedReplacements = 0;
		for (; $this->currentIndex < $this->tokens->count(); ++$this->currentIndex) {
			if (!$this->tokens[$this->currentIndex]->isGivenKind(T_START_HEREDOC)) {
				continue;
			}

			$delimiter = $this->extractDelimiter($this->tokens[$this->currentIndex]);
			++$this->currentIndex;
			$segments = [];
			$lastToken = null;
			for ($index = $this->currentIndex; $index < $this->tokens->count(); ++$index) {
				if ($this->tokens[$index]->isGivenKind(T_END_HEREDOC)) {
					if ($lastToken !== null) {
						$segments[] = $lastToken;
					}
					$indentation = $this->extractIndentation($this->tokens[$index]);
					return new BlockString($delimiter, $indentation, $this->removeTrailingNewLine($segments));
				}
				if ($this->tokens[$index]->isGivenKind(T_ENCAPSED_AND_WHITESPACE)) {
					if ($lastToken !== null) {
						$segments[] = $lastToken;
					}
					$segments[] = new StringSegment($this->tokens[$index]->getContent());
					$lastToken = null;
				} else {
					if ($lastToken === null) {
						$lastToken = new InterpolationSegment();
					}
					$lastToken->innerTokens[] = $this->tokens[$index];
				}
				$this->expectedReplacements++;
			}

			// This probably should never happen - I don't think we can actually parse such corrupted php code
			throw new RuntimeException('Unterminated block string');
		}

		$this->expectedReplacements = 0;
		return null;
	}

	private function extractDelimiter(Token $token): string
	{
		return trim($token->getContent(), "<'\"\n");
	}

	private function extractIndentation(Token $token): string
	{
		return preg_match('/^[^\S\n\r]*/', $token->getContent(), $matches) === 1 ? $matches[0] : '';
	}

	/**
	 * @param list<SegmentInterface> $segments
	 * @return list<SegmentInterface>
	 */
	private function removeTrailingNewLine(array $segments): array
	{
		$segmentCount = count($segments);
		if ($segmentCount === 0) {
			throw new RuntimeException('BlockString should have at least one segment');
		}

		$lastSegment = $segments[$segmentCount - 1];
		if (!$lastSegment instanceof StringSegment) {
			throw new RuntimeException('Last BlockString segment must be a string segment');
		}

		$segments[$segmentCount - 1] = $lastSegment->withValue(substr($lastSegment->value, 0, -1));

		return array_values($segments);
	}

	public function replace(BlockString $replacement): void
	{
		$newTokens = [];
		foreach ($this->appendTrailingNewLine($replacement->segments) as $segment) {
			if ($segment instanceof StringSegment) {
				$newTokens[] = new Token([T_ENCAPSED_AND_WHITESPACE, $segment->value]);
				continue;
			}

			assert($segment instanceof InterpolationSegment);
			foreach ($segment->innerTokens as $token) {
				$newTokens[] = $token;
			}
		}

		$this->tokens->overrideRange($this->currentIndex, $this->currentIndex + $this->expectedReplacements - 1, $newTokens);
	}

	/**
	 * @param list<SegmentInterface> $segments
	 * @return list<SegmentInterface>
	 */
	private function appendTrailingNewLine(array $segments): array
	{
		$segmentCount = count($segments);
		if ($segmentCount === 0) {
			throw new RuntimeException('BlockString should have at least one segment');
		}

		$lastSegment = $segments[$segmentCount - 1];
		if (!$lastSegment instanceof StringSegment) {
			throw new RuntimeException('Last BlockString segment must be a string segment');
		}

		$segments[$segmentCount - 1] = $lastSegment->withValue("{$lastSegment->value}\n");

		return array_values($segments);
	}
}
