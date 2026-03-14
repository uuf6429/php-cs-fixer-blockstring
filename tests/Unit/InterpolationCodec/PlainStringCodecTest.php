<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\InterpolationCodec;

use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\BlockString\InterpolationSegment;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\PlainStringCodec;

/**
 * @internal
 */
final class PlainStringCodecTest extends TestCase
{
	public function testThatPlainStringCodecRequiresOneSegment(): void
	{
		$codec = new PlainStringCodec();

		$this->expectExceptionObject(
			new \RuntimeException(
				'PlainStringCodec does not support string interpolation by default'
			)
		);

		$codec->encode([new StringSegment('a'), new StringSegment('b')]);
	}

	public function testThatPlainStringCodecRequiresStringSegment(): void
	{
		$codec = new PlainStringCodec();

		$this->expectExceptionObject(
			new \RuntimeException(
				'PlainStringCodec does not support string interpolation by default'
			)
		);

		$codec->encode([new InterpolationSegment([])]);
	}
}
