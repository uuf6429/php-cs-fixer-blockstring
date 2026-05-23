<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Fixtures\Formatters;

use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractStringFormatter;

/**
 * @internal
 */
final class TagWrapper extends AbstractStringFormatter
{
	private string $tagName;

	public function __construct(string $tagName)
	{
		parent::__construct(self::class . " ($tagName)");

		$this->tagName = $tagName;
	}

	protected function formatContent(string $original): string
	{
		return "<{$this->tagName}>$original</{$this->tagName}>";
	}
}
