<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\BlockString;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use uuf6429\PhpCsFixerBlockstring\BlockString\InterpolationSegment;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\BlockString\TokenStream;

final class TokenStreamTest extends TestCase
{
	public function testSimpleHeredoc(): void
	{
		$code = "<<<EOF\nhello\nEOF;";
		$tokens = Tokens::fromCode("<?php\necho $code");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$blockString = $stream->next();

		$this->assertNotNull($blockString);
		$this->assertSame('EOF', $blockString->delimiter);
		$this->assertSame('', $blockString->indentation);
		$this->assertCount(1, $blockString->segments);
		$this->assertInstanceOf(StringSegment::class, $blockString->segments[0]);
		$this->assertSame('hello', $blockString->segments[0]->value);

		$this->assertNull($stream->next());
	}

	public function testNowdoc(): void
	{
		$code = "<<<'EOF'\nhello\nEOF;";
		$tokens = Tokens::fromCode("<?php\necho $code");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$blockString = $stream->next();

		$this->assertNotNull($blockString);
		$this->assertSame('EOF', $blockString->delimiter);
		$this->assertCount(1, $blockString->segments);
		$this->assertSame('hello', (string)$blockString->segments[0]);
	}

	public function testHeredocWithInterpolation(): void
	{
		$code = "<<<EOF\nHello \$name!\nEOF;";
		$tokens = Tokens::fromCode("<?php\necho $code");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$blockString = $stream->next();

		$this->assertNotNull($blockString);
		$this->assertCount(3, $blockString->segments);
		$this->assertInstanceOf(StringSegment::class, $blockString->segments[0]);
		$this->assertSame('Hello ', $blockString->segments[0]->value);
		$this->assertInstanceOf(InterpolationSegment::class, $blockString->segments[1]);
		$this->assertSame('$name', $blockString->segments[1]->asString());
		$this->assertInstanceOf(StringSegment::class, $blockString->segments[2]);
		$this->assertSame('!', $blockString->segments[2]->value);
	}

	public function testHeredocWithComplexInterpolation(): void
	{
		$code = "<<<EOF\n{\$obj->method(\$a + \$b)}\nEOF;";
		$tokens = Tokens::fromCode("<?php\necho $code");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$blockString = $stream->next();

		$this->assertNotNull($blockString);
		$this->assertCount(2, $blockString->segments);
		$this->assertInstanceOf(InterpolationSegment::class, $blockString->segments[0]);
		$this->assertSame('{$obj->method($a + $b)}', $blockString->segments[0]->asString());
		$this->assertInstanceOf(StringSegment::class, $blockString->segments[1]);
		$this->assertSame('', $blockString->segments[1]->value);
	}

	public function testMultipleHeredocs(): void
	{
		$code = "<<<A\n1\nA;\necho <<<B\n2\nB;";
		$tokens = Tokens::fromCode("<?php\necho $code");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$a = $stream->next();
		$this->assertNotNull($a);
		$this->assertSame('A', $a->delimiter);
		$this->assertSame('1', (string)$a->segments[0]);

		$b = $stream->next();
		$this->assertNotNull($b);
		$this->assertSame('B', $b->delimiter);
		$this->assertSame('2', (string)$b->segments[0]);

		$this->assertNull($stream->next());
	}

	public function testReplace(): void
	{
		$code = "<<<EOF\nhello\nEOF;";
		$tokens = Tokens::fromCode("<?php\necho $code");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$blockString = $stream->next();
		$this->assertNotNull($blockString);

		$newSegments = [new StringSegment('world')];
		$stream->replace($blockString->withSegments($newSegments));

		$this->assertSame("<?php\necho <<<EOF\nworld\nEOF;", $tokens->generateCode());
	}

	public function testReplaceWithInterpolation(): void
	{
		$code = "<<<EOF\nhello\nEOF;";
		$tokens = Tokens::fromCode("<?php\necho $code");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$blockString = $stream->next();
		$this->assertNotNull($blockString);

		$newSegments = [
			new StringSegment('Hello '),
			new InterpolationSegment([new Token([T_VARIABLE, '$name'])]),
			new StringSegment('!')
		];
		$stream->replace($blockString->withSegments($newSegments));

		$this->assertSame("<?php\necho <<<EOF\nHello \$name!\nEOF;", $tokens->generateCode());
	}

	public function testUnterminatedBlockStringThrowsException(): void
	{
		$tokens = new Tokens();
		$tokens->insertAt(0, new Token([T_OPEN_TAG, '<?php ']));
		$tokens->insertAt(1, new Token([T_START_HEREDOC, "<<<EOF\n"]));

		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$this->expectExceptionObject(new RuntimeException('Unterminated block string'));
		$stream->next();
	}

	public function testReplaceWithDifferentTokenCount(): void
	{
		$code = "<<<EOF\n1\nEOF;\necho <<<EOF\n2\nEOF;";
		$tokens = Tokens::fromCode("<?php\necho $code");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$a = $stream->next();
		$this->assertNotNull($a);

		$newSegments = [
			new StringSegment("1\n"),
			new InterpolationSegment([new Token([T_VARIABLE, '$x'])]),
			new StringSegment("\n3")
		];
		$stream->replace($a->withSegments($newSegments));

		$b = $stream->next();
		$this->assertNotNull($b);
		$this->assertSame('2', (string)$b->segments[0]);
	}

	public function testEmptyHeredocThrowsException(): void
	{
		$tokens = Tokens::fromCode("<?php\n<<<EOF\nEOF;");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$this->expectExceptionObject(new RuntimeException('BlockString should have at least one segment'));
		$stream->next();
	}

	public function testHeredocEndingWithInterpolationThrowsException(): void
	{
		$tokens = new Tokens();
		$tokens->insertAt(0, new Token([T_OPEN_TAG, '<?php ']));
		$tokens->insertAt(1, new Token([T_START_HEREDOC, "<<<EOF\n"]));
		$tokens->insertAt(2, new Token([T_VARIABLE, '$a']));
		$tokens->insertAt(3, new Token([T_END_HEREDOC, 'EOF']));

		$stream = TokenStream::fromPhpCsFixerTokens($tokens);

		$this->expectExceptionObject(new RuntimeException('Last BlockString segment must be a string segment'));
		$stream->next();
	}

	public function testReplaceEmptySegmentsThrowsException(): void
	{
		$tokens = Tokens::fromCode("<?php\n<<<EOF\nhello\nEOF;");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);
		$blockString = $stream->next();
		assert($blockString !== null);

		$this->expectExceptionObject(new RuntimeException('BlockString should have at least one segment'));
		$stream->replace($blockString->withSegments([]));
	}

	public function testReplaceLastSegmentNotStringThrowsException(): void
	{
		$tokens = Tokens::fromCode("<?php\n<<<EOF\nhello\nEOF;");
		$stream = TokenStream::fromPhpCsFixerTokens($tokens);
		$blockString = $stream->next();
		assert($blockString !== null);

		$this->expectExceptionObject(new RuntimeException('Last BlockString segment must be a string segment'));
		$stream->replace($blockString->withSegments([new InterpolationSegment()]));
	}
}
