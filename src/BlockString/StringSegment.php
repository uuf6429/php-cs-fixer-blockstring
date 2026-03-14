<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\BlockString;

final class StringSegment implements SegmentInterface
{
	/**
	 * @readonly
	 */
	public string $value;

	public function __construct(string $value)
	{
		$this->value = $value;
	}

	public function __toString(): string
	{
		return $this->value;
	}

	public function withValue(string $value): self
	{
		return new self($value);
	}
}
