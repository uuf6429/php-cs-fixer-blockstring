<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring;

interface CacheFingerprintableInterface
{
	/**
	 * This method should return a unique representation of the object and its state, to be used as a cache identifier.
	 * **Important:** Make to return simple values (null, scalar or arrays) only. Avoid closure, objects etc.
	 *
	 * @return mixed
	 */
	public function getCacheFingerprint();
}
