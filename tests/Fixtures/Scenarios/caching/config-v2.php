<?php declare(strict_types=1);

use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;
use uuf6429\PhpCsFixerBlockstringTests\Fixtures\Formatters\JsonCommenter;

return (new PhpCsFixer\Config())
	->registerCustomFixers([new BlockStringFixer()])
	->setRiskyAllowed(true)
	->setRules([
		BlockStringFixer::NAME => BlockStringFixer::config(
			[
				'JSON' => new JsonCommenter('JsonCommenter v2'),
			]
		)
	]);
