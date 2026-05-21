<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\InterpolationCodec;

use LogicException;
use PhpCsFixer\Tokenizer\Token;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use uuf6429\PhpCsFixerBlockstring\BlockString\InterpolationSegment;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\CacheFingerprintableInterface;
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

	public function testThatTokenFactoryCanBeSimpleCallable(): void
	{
		/** @phpstan-ignore argument.type */
		$codec = new GeneratedTokenCodec('<some-pattern>', 'array_map');

		$this->assertSame([GeneratedTokenCodec::class, '<some-pattern>', 'array_map'], $codec->getCacheFingerprint());
	}

	public function testThatTokenFactoryCannotBeBeSimpleClosure(): void
	{
		$codec = new GeneratedTokenCodec('<some-pattern>', static fn() => null);

		$this->expectExceptionObject(new RuntimeException('Token factory must implement CacheFingerprintableInterface'));

		$codec->getCacheFingerprint();
	}

	public function testThatTokenFactoryCanBeCachableInvokable(): void
	{
		$codec = new GeneratedTokenCodec('<some-pattern>', new class implements CacheFingerprintableInterface {
			public function __invoke(): string
			{
				return 'xx';
			}

			public function getCacheFingerprint()
			{
				return 'fingerprint';
			}
		});

		$this->assertSame([GeneratedTokenCodec::class, '<some-pattern>', 'fingerprint'], $codec->getCacheFingerprint());
	}
}
