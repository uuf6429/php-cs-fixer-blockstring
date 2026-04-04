<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\InterpolationCodec;

use LogicException;
use PhpCsFixer\Tokenizer\Token;
use PHPUnit\Framework\TestCase;
use uuf6429\PhpCsFixerBlockstring\BlockString\InterpolationSegment;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecResult;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\GeneratedTokenCodec;

/**
 * @internal
 */
final class GeneratedTokenCodecTest extends TestCase
{
	public function testThatTokenPatternCannotReturnEmptyString(): void
	{
		$codec = new GeneratedTokenCodec('');

		$this->expectExceptionObject(
			new LogicException(
				'Replacement token cannot be an empty string!'
			)
		);

		$codec->encode([new InterpolationSegment([])]);
	}

	public function testThatTokenFactoryCannotReturnEmptyString(): void
	{
		$codec = new GeneratedTokenCodec('some pattern', static function () {
			return '';
		});

		$this->expectExceptionObject(
			new LogicException(
				'Replacement token cannot be an empty string!'
			)
		);

		$codec->encode([new InterpolationSegment([])]);
	}

	public function testThatDefaultBehaviourTriggeredWhenTokenFactoryReturnsNull(): void
	{
		$codec = new GeneratedTokenCodec('<some-pattern>', static function ($inter) {
			return (string)$inter === '$var1' ? '<custom-pattern>' : null;
		});

		$result = $codec->encode([
			new StringSegment('aa'),
			$var1 = new InterpolationSegment([new Token([T_VARIABLE, '$var1'])]),
			$var2 = new InterpolationSegment([new Token([T_VARIABLE, '$var2'])]),
			new StringSegment('bb'),
		]);

		$this->assertEquals(
			new CodecResult(
				[
					'<custom-pattern>' => $var1,
					'<some-pattern>' => $var2,
				],
				'aa<custom-pattern><some-pattern>bb'
			),
			$result
		);
	}
}
