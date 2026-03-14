<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\BlockString;

final class BlockString
{
	/**
	 * @readonly
	 */
	public string $delimiter;

	/**
	 * @readonly
	 */
	public string $indentation;

	/**
	 * @readonly
	 * @var list<SegmentInterface>
	 */
	public array $segments;

	/**
	 * @param list<SegmentInterface> $segments
	 */
	public function __construct(string $delimiter, string $indentation, array $segments)
	{
		$this->delimiter = $delimiter;
		$this->indentation = $indentation;
		$this->segments = $segments;
	}

	/**
	 * @param list<SegmentInterface> $newSegments
	 */
	public function withSegments(array $newSegments): self
	{
		return new self($this->delimiter, $this->indentation, $newSegments);
	}
}
