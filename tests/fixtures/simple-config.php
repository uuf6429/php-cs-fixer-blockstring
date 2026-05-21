<?php declare(strict_types=1);

use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;
use uuf6429\PhpCsFixerBlockstring\Formatter;

return (new PhpCsFixer\Config())
	->registerCustomFixers([new BlockStringFixer()])
	->setRiskyAllowed(true)
	->setRules([
		BlockStringFixer::NAME => [
			'formatters' => [
				'JSON' => new class extends Formatter\AbstractStringFormatter {
					public function __construct()
					{
						parent::__construct(getenv('TEST_FORMATTER_CACHE_FINGERPRINT'));
					}

					public function formatContent(string $original): string
					{
						return json_encode(
							json_decode($original, false, 512, JSON_THROW_ON_ERROR),
							JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
						);
					}
				},
			],
		],
	]);
