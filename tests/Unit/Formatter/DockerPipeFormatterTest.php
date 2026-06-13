<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Unit\Formatter;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\BlockString\StringSegment;
use uuf6429\PhpCsFixerBlockstring\Formatter\DockerPipeFormatter;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessFactoryInterface;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessFailedException;
use uuf6429\PhpCsFixerBlockstring\Process\ProcessInterface;
use uuf6429\PhpCsFixerBlockstringTests\MockProcessFactoryTrait;

/**
 * @internal
 */
final class DockerPipeFormatterTest extends TestCase
{
	use MockProcessFactoryTrait;

	public function testFormatWithNeverPullMode(): void
	{
		$formatter = new DockerPipeFormatter(
			'my-image',
			[],
			[],
			'never',
			null,
			null,
			$this->createProcessFactoryMock([
				[
					[['docker', 'image', 'inspect', 'my-image', '--format={{.Os}}/{{.Architecture}} {{.Id}}'], null, null, null],
					$this->createProcessMock("linux/amd64 sha256:digest123\n"),
				],
				[
					[['docker', 'run', '--rm', '--interactive', 'sha256:digest123'], null, null, 'input content'],
					$this->createProcessMock('formatted content'),
				],
			])
		);

		$input = new BlockString('', '', [new StringSegment('input content')]);
		$output = $formatter->formatBlock($input);

		$this->assertSame('formatted content', implode('', $output->segments));
	}

	public function testFormatWithAlwaysPullMode(): void
	{
		$formatter = new DockerPipeFormatter(
			'my-image',
			[],
			[],
			'always',
			null,
			null,
			$this->createProcessFactoryMock([
				[
					[['docker', 'image', 'pull', 'my-image'], null, null, null],
					$this->createProcessMock("dummy docker pull output\n"),
				],
				[
					[['docker', 'image', 'inspect', 'my-image', '--format={{.Os}}/{{.Architecture}} {{.Id}}'], null, null, null],
					$this->createProcessMock("linux/amd64 sha256:digest123\n"),
				],
				[
					[['docker', 'run', '--rm', '--interactive', 'sha256:digest123'], null, null, 'input content'],
					$this->createProcessMock('formatted content'),
				],
			])
		);

		$input = new BlockString('', '', [new StringSegment('input content')]);
		$output = $formatter->formatBlock($input);

		$this->assertSame('formatted content', implode('', $output->segments));
	}

	public function testFormatWithMissingPullModeWhenImageIsMissing(): void
	{
		$formatter = new DockerPipeFormatter(
			'my-image',
			[],
			[],
			'missing',
			null,
			null,
			$this->createProcessFactoryMock([
				[
					[],
					$this->createFailingProcessMock('No such image'),
				],
				[
					[['docker', 'image', 'pull', 'my-image'], null, null, null],
					$this->createProcessMock("dummy docker pull output\n"),
				],
				[
					[['docker', 'image', 'inspect', 'my-image', '--format={{.Os}}/{{.Architecture}} {{.Id}}'], null, null, null],
					$this->createProcessMock("linux/amd64 sha256:digest123\n"),
				],
				[
					[['docker', 'run', '--rm', '--interactive', 'sha256:digest123'], null, null, 'input content'],
					$this->createProcessMock('formatted content'),
				],
			])
		);

		$input = new BlockString('', '', [new StringSegment('input content')]);
		$output = $formatter->formatBlock($input);

		$this->assertSame('formatted content', implode('', $output->segments));
	}

	public function testInspectFailureThrowsException(): void
	{
		$process = $this->createMock(ProcessInterface::class);
		$process->method('mustRun')->willThrowException(new ProcessFailedException('No such image'));
		$process->method('getErrorOutput')->willReturn('No such image');

		$processFactory = $this->createMock(ProcessFactoryInterface::class);
		$processFactory->method('create')->willReturn($process);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No such image');

		new DockerPipeFormatter('my-image', [], [], 'never', null, null, $processFactory);
	}

	public function testInvalidPullModeThrowsException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unsupported Pull Mode: invalid');

		// @phpstan-ignore-next-line
		new DockerPipeFormatter('my-image', [], [], 'invalid');
	}
}
