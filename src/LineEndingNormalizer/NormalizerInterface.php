<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer;

use uuf6429\PhpCsFixerBlockstring\CacheFingerprintableInterface;

interface NormalizerInterface extends CacheFingerprintableInterface
{
	public function normalize(string $formatted, string $original): string;
}
