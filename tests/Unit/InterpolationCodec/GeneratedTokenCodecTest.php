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

	/**
	 * @dataProvider decodeDataProvider
	 *
	 * @param array<string, InterpolationSegment> $mapping
	 * @param list<StringSegment|InterpolationSegment> $expectedSegments
	 */
	public function testDecode(array $mapping, string $content, array $expectedSegments): void
	{
		$codec = new GeneratedTokenCodec();
		$result = new CodecResult($mapping, $content);

		$this->assertEquals($expectedSegments, $codec->decode($result));
	}

	/**
	 * @return iterable<string, array{
	 *     mapping: array<string, InterpolationSegment>,
	 *     content: string,
	 *     expectedSegments: list<StringSegment|InterpolationSegment>,
	 * }>
	 */
	public static function decodeDataProvider(): iterable
	{
		yield 'empty' => [
			'mapping' => [],
			'content' => '',
			'expectedSegments' => [],
		];

		yield 'only string' => [
			'mapping' => [],
			'content' => 'hello world',
			'expectedSegments' => [new StringSegment('hello world')],
		];

		$s1 = new InterpolationSegment([]);
		yield 'only token' => [
			'mapping' => ['{T1}' => $s1],
			'content' => '{T1}',
			'expectedSegments' => [$s1],
		];

		$s1 = new InterpolationSegment([]);
		yield 'token with surrounding text' => [
			'mapping' => ['{T1}' => $s1],
			'content' => 'aa{T1}bb',
			'expectedSegments' => [
				new StringSegment('aa'),
				$s1,
				new StringSegment('bb'),
			],
		];

		$s1 = new InterpolationSegment([]);
		$s2 = new InterpolationSegment([]);
		yield 'multiple tokens' => [
			'mapping' => [
				'{T1}' => $s1,
				'{T2}' => $s2,
			],
			'content' => 'aa{T1}bb{T2}cc',
			'expectedSegments' => [
				new StringSegment('aa'),
				$s1,
				new StringSegment('bb'),
				$s2,
				new StringSegment('cc'),
			],
		];

		$s1 = new InterpolationSegment([]);
		$s2 = new InterpolationSegment([]);
		yield 'overlapping tokens (longest match)' => [
			'mapping' => [
				'ABC' => $s1,
				'AB' => $s2,
			],
			'content' => 'ABC',
			'expectedSegments' => [$s1],
		];

		$s1 = new InterpolationSegment([]);
		$s2 = new InterpolationSegment([]);
		yield 'overlapping tokens (shortest match starts later)' => [
			'mapping' => [
				'ABC' => $s1,
				'BC' => $s2,
			],
			'content' => 'ABC',
			'expectedSegments' => [$s1],
		];

		yield 'no match' => [
			'mapping' => ['{T1}' => new InterpolationSegment([])],
			'content' => 'hello world',
			'expectedSegments' => [new StringSegment('hello world')],
		];

		$s1 = new InterpolationSegment([]);
		yield 'match at the end' => [
			'mapping' => ['{T1}' => $s1],
			'content' => 'hello {T1}',
			'expectedSegments' => [
				new StringSegment('hello '),
				$s1,
			],
		];

		$s1 = new InterpolationSegment([]);
		yield 'multiple occurrences of same token' => [
			'mapping' => ['{T1}' => $s1],
			'content' => '{T1} and {T1}',
			'expectedSegments' => [
				$s1,
				new StringSegment(' and '),
				$s1,
			],
		];

		$s1 = new InterpolationSegment([]);
		$s2 = new InterpolationSegment([]);
		yield 'partial match of longer token' => [
			'mapping' => [
				'ABC' => $s1,
				'AB' => $s2,
			],
			'content' => 'ABD',
			'expectedSegments' => [
				$s2,
				new StringSegment('D'),
			],
		];
	}
}
