<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstringTests\Fixtures\Formatters;

use uuf6429\PhpCsFixerBlockstring\Formatter\AbstractStringFormatter;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\GeneratedTokenCodec;

/**
 * @internal
 */
final class JsonObjectKeySorter extends AbstractStringFormatter
{
	public function __construct()
	{
		parent::__construct('ObjectKeySorter v1.0', new GeneratedTokenCodec('"__PHP_VAR_%d__"'));
	}

	public function formatContent(string $original): string
	{
		return json_encode(
			$this->sortObjectKeysRecursively(
				json_decode(
					$original,
					false,
					512,
					JSON_THROW_ON_ERROR
				)
			),
			JSON_THROW_ON_ERROR
		);
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private function sortObjectKeysRecursively($value)
	{
		if (is_object($value)) {
			$value = get_object_vars($value);
			ksort($value);
			return (object)$value;
		}

		if (is_array($value)) {
			return array_map([$this, 'sortObjectKeysRecursively'], $value);
		}

		return $value;
	}
}
