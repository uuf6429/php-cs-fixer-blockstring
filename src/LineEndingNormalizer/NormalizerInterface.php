<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\LineEndingNormalizer;

interface NormalizerInterface
{
	public function normalize(string $formatted, string $original): string;
}
