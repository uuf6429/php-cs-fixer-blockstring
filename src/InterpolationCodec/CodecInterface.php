<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\InterpolationCodec;

use uuf6429\PhpCsFixerBlockstring\BlockString\SegmentInterface;

interface CodecInterface
{
	/**
	 * @param list<SegmentInterface> $segments
	 */
	public function encode(array $segments): CodecResult;

	/**
	 * @return list<SegmentInterface>
	 */
	public function decode(CodecResult $result): array;
}
