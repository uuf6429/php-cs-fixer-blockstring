<?php declare(strict_types=1);

namespace uuf6429\PhpCsFixerBlockstring\Formatter;

use uuf6429\PhpCsFixerBlockstring\BlockString\BlockString;

/**
 * This formatter allows multiple formatters to be applied sequentially - the output of each formatter becomes the
 * input of the next one.
 *
 * Example:
 *
 *  ```php
 *  ['formatters' => [ new ChainFormatter (
 *      new FirstFormatter(),
 *      new SecondFormatter(),
 *  ) ]]
 *  ```
 */
final class ChainFormatter extends AbstractFormatter
{
	/**
	 * @var list<AbstractFormatter>
	 */
	private array $formatters;

	/**
	 * @param AbstractFormatter ...$formatters
	 */
	public function __construct(AbstractFormatter ...$formatters)
	{
		parent::__construct('1.0');

		$this->formatters = array_values($formatters);
	}

	public function formatBlock(BlockString $blockString): BlockString
	{
		$result = $blockString;
		foreach ($this->formatters as $formatter) {
			$result = $formatter->formatBlock($result);
		}
		return $result;
	}
}
