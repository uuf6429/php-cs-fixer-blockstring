<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;
use uuf6429\PhpCsFixerBlockstring\CacheFingerprintableInterface;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\CodecInterface;

/**
 * This is the base class of all formatters. In most cases you don't really want to extend this class, since it does
 * not handle string interpolation at all – check out {@see AbstractStringFormatter} instead.
 *
 * Extending this class makes sense in two situations:
 *
 * 1. If your class is infrastructural, and you don't really need to handle string interpolation - just like
 *    {@see ChainFormatter}
 * 2. Or if, for whatever reason, the {@see CodecInterface} concept does not work for you and you want to write
 *    something from scratch.
 */
abstract class AbstractFormatter implements CacheFingerprintableInterface
{
	/**
	 * @var mixed
	 * @readonly
	 */
	private $cacheFingerprint;

	/**
	 * @param mixed $cacheFingerprint A unique representation of the formatter logic and its configuration, used for
	 * caching purposes. For example, if the formatter executes some cli command with a specific version, the
	 * fingerprint should contain:
	 * - the formatter class (to distguish from other formatters)
	 * - the cli command name (since it's a setting of the formatter)
	 * - the cli command version (in case the cli command gets updated at some point)
	 *
	 * **Important:** Make sure that the fingerprint contains simple values (null, scalar or arrays).
	 */
	public function __construct($cacheFingerprint)
	{
		$this->cacheFingerprint = $cacheFingerprint;
	}

	/**
	 * Format the provided BlockString accordingly and return a new one.
	 */
	abstract public function formatBlock(BlockString $blockString): BlockString;

	/**
	 * @return mixed
	 */
	final public function getCacheFingerprint()
	{
		return $this->cacheFingerprint;
	}
}
