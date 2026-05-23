<?php declare(strict_types=1);

use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;
use uuf6429\PhpCsFixerBlockstring\Formatter;

class JsonFormatterV1 extends Formatter\AbstractStringFormatter
{
	public function __construct()
	{
		parent::__construct(self::class);
	}

	public function formatContent(string $original): string
	{
		return json_encode(
			array_merge(
				['_comment' => self::class],
				(array)json_decode($original, false, 512, JSON_THROW_ON_ERROR)
			),
			JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
		);
	}
}

return (new PhpCsFixer\Config())
	->registerCustomFixers([new BlockStringFixer()])
	->setRiskyAllowed(true)
	->setRules([
		BlockStringFixer::NAME => BlockStringFixer::config(
			[
				'JSON' => new JsonFormatterV1(),
			]
		)
	]);
