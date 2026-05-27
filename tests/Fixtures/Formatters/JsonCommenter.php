<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Fixtures\Formatters;

use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractStringFormatter;

/**
 * @internal
 */
final class JsonCommenter extends AbstractStringFormatter
{
	private string $comment;

	public function __construct(string $comment)
	{
		parent::__construct(self::class . " ($comment)");

		$this->comment = $comment;
	}

	public function formatContent(string $original): string
	{
		return json_encode(
			array_merge(
				(array)json_decode($original, false, 512, JSON_THROW_ON_ERROR),
				['_comment' => $this->comment]
			),
			JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
		);
	}
}
