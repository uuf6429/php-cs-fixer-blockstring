<?php declare(strict_types=1);

use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;
use uuf6429\PhpCsFixerBlockstring\Formatter;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\GeneratedTokenCodec;

return (new PhpCsFixer\Config())
	->registerCustomFixers([new BlockStringFixer()])
	->setRiskyAllowed(true)
	->setRules([
		BlockStringFixer::NAME => [
			'formatters' => [

				// 1️⃣ SimpleLineFormatter
				// Normalizes indentation of any block not explicitly configured below
				new Formatter\SimpleLineFormatter(
					4,                // indentSize
					"\t",             // indentChar
					new GeneratedTokenCodec()   // interpolationCodec
				),

				// 2️⃣ CliPipeFormatter
				// Formats SQL using a CLI tool installed locally
				'SQL' => new Formatter\CliPipeFormatter(
					['cmd' => ['php', __DIR__ . '/sqlformat.php', '--version']],      // versionValueOrCommand
					['cmd' => ['php', __DIR__ . '/sqlformat.php', '-']],              // formatCommand
				),

				// 3️⃣ ChainFormatter
				// Combines two formatters:
				// 1. A custom formatter that sorts object keys.
				// 2. A docker-based formatter that runs the json through jq.
				'JSON' => new Formatter\ChainFormatter(
					new class extends Formatter\AbstractStringFormatter {
						public function __construct()
						{
							parent::__construct('1.0', new GeneratedTokenCodec('"__PHP_VAR_%d__"'));
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
					},
					new Formatter\DockerPipeFormatter(
						'ghcr.io/jqlang/jq',                               // image
						[],                                                // options
						[],                                                // command
						'missing',                                         // pullMode
						new GeneratedTokenCodec('"__PHP_VAR_%d__"')        // interpolationCodec
					),
				),

			],
		],
	]);
