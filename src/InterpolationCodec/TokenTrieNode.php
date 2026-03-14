<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\InterpolationCodec;

final class TokenTrieNode
{
	/** @var array<string, TokenTrieNode> */
	public array $children = [];

	public ?string $token = null;

	/**
	 * @param array<string, mixed> $mapping
	 */
	public static function fromMapping(array $mapping): self
	{
		$root = new self();

		foreach (array_keys($mapping) as $token) {
			$root->insert($token);
		}

		return $root;
	}

	public function insert(string $token): void
	{
		$node = $this;
		foreach (str_split($token) as $char) {
			$node = $node->children[$char] ?? ($node->children[$char] = new self());
		}
		$node->token = $token;
	}
}
