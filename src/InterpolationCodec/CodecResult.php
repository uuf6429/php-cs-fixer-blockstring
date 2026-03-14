<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\InterpolationCodec;

use uuf6429\PhpCsFixerBlockstring\BlockString\InterpolationSegment;

final class CodecResult
{
	/**
	 * @readonly
	 * @var array<string, InterpolationSegment>
	 */
	public array $mapping;

	/**
	 * @readonly
	 */
	public string $content;

	/**
	 * @param array<string, InterpolationSegment> $mapping
	 */
	public function __construct(array $mapping, string $content)
	{
		$this->mapping = $mapping;
		$this->content = $content;
	}

	public function withContent(string $newContent): self
	{
		return new self($this->mapping, $newContent);
	}
}
