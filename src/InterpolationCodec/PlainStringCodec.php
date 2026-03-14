<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\InterpolationCodec;

use RuntimeException;
use uuf6429\PhpCsFixerBlockstring\BlockString\InterpolationSegment;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;

final class PlainStringCodec implements CodecInterface
{
	public function encode(array $segments): CodecResult
	{
		if (count($segments) !== 1 || !($segments[0] instanceof StringSegment)) {
			throw new RuntimeException('PlainStringCodec does not support string interpolation by default');
		}

		return new CodecResult([], $segments[0]->value);
	}

	public function decode(CodecResult $result): array
	{
		return [new StringSegment($result->content)];
	}
}
