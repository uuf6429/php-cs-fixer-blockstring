<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\InterpolationCodec;

use LogicException;
use uuf6429\PhpCsFixerBlockstring\BlockString\InterpolationSegment;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;

final class GeneratedTokenCodec implements CodecInterface
{
	/**
	 * @readonly
	 */
	private string $tokenPattern;

	/**
	 * @readonly
	 * @var (callable(InterpolationSegment $segment): ?string)|null
	 */
	private $tokenFactory;

	/**
	 * @param string $tokenPattern
	 * @param (callable(InterpolationSegment $segment): ?string)|null $tokenFactory
	 */
	public function __construct(string $tokenPattern = '__PHP_VAR_%d__', ?callable $tokenFactory = null)
	{
		$this->tokenPattern = $tokenPattern;
		$this->tokenFactory = $tokenFactory;
	}

	public function encode(array $segments): CodecResult
	{
		$index = 0;
		$mapping = [];
		$content = '';
		foreach ($segments as $segment) {
			if ($segment instanceof StringSegment) {
				$content .= $segment->value;
				continue;
			}

			assert($segment instanceof InterpolationSegment);
			$token = null;
			if ($this->tokenFactory !== null) {
				$token = ($this->tokenFactory)($segment);
			}
			if ($token === null) {
				$token = sprintf($this->tokenPattern, ++$index);
			}
			if ($token === '') {
				throw new LogicException('Replacement token cannot be an empty string!');
			}
			$mapping[$token] = $segment;
			$content .= $token;
		}

		return new CodecResult($mapping, $content);
	}

	public function decode(CodecResult $result): array
	{
		$content = $result->content;
		$len = strlen($content);
		$pos = 0;
		$root = TokenTrieNode::fromMapping($result->mapping);

		$segments = [];
		while ($pos < $len) {
			$node = $root;
			$curPos = $pos;
			$matchPos = 0;
			$matchToken = null;

			while ($curPos < $len && ($node = $node->children[$content[$curPos] ?? ''] ?? null) !== null) {
				$curPos++;
				if ($node->token !== null) {
					$matchToken = $node->token;
					$matchPos = $curPos;
				}
			}

			if ($matchToken !== null) {
				if ($pos < $matchPos - strlen($matchToken)) {
					$segments[] = new StringSegment(substr($content, $pos, $matchPos - strlen($matchToken) - $pos));
				}
				$segments[] = $result->mapping[$matchToken];
				$pos = $matchPos;
			} else {
				$start = $pos;
				$pos++;
				while ($pos < $len) {
					$node = $root;
					$cur = $pos;
					while ($cur < $len && isset($node->children[$content[$cur]])) {
						$node = $node->children[$content[$cur]];
						$cur++;
						if ($node->token !== null) {
							break 2;
						}
					}
					$pos++;
				}
				$segments[] = new StringSegment(substr($content, $start, $pos - $start));
			}
		}

		return $segments;
	}
}
