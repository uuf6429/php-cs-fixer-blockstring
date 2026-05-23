<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Fixtures\Formatters;

use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractStringFormatter;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

/**
 * @internal
 */
final class HtmlTagStripper extends AbstractStringFormatter
{
	public function __construct(?CodecInterface $interpolationCodec)
	{
		parent::__construct(self::class, $interpolationCodec);
	}

	protected function formatContent(string $original): string
	{
		return strip_tags($original);
	}
}
