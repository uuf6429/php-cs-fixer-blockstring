<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

/**
 * This is the base class of all formatters. In most cases you don't really want to extend this class, since it does
 * not handle string interpolation at all - check out {@see AbstractStringFormatter} instead.
 *
 * Extending this class makes sense in two situations:
 *
 * 1. If your class is infrastructural, and you don't really need to handle string interpolation - just like
 *    {@see ChainFormatter}
 * 2. Or if, for whatever reason, the {@see CodecInterface} concept does not work for you and you want to write
 *    something from scratch.
 */
abstract class AbstractFormatter
{
	/**
	 * @readonly
	 */
	protected string $version;

	/**
	 * @param string $version A string representing a version of this formatter, used for caching purposes.
	 * For example, if the formatting algorithm/logic is changed, the version should also be different.
	 */
	public function __construct(string $version)
	{
		$this->version = $version;
	}

	/**
	 * Format the provided BlockString accordingly and return a new one.
	 */
	abstract public function formatBlock(BlockString $blockString): BlockString;
}
