<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\BlockString;

use PhpCsFixer\Tokenizer\Token;

final class InterpolationSegment implements SegmentInterface
{
	/**
	 * @var list<Token>
	 */
	public array $innerTokens;

	/**
	 * @param list<Token> $innerTokens
	 */
	public function __construct(array $innerTokens = [])
	{
		$this->innerTokens = $innerTokens;
	}

	public function __toString(): string
	{
		return $this->asString();
	}

	public function asString(): string
	{
		return implode('', array_map(static fn(Token $token) => $token->getContent(), $this->innerTokens));
	}
}
