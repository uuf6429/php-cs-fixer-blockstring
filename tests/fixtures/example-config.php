<?php declare(strict_types=1);

use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;
use uuf6429\PhpCsFixerBlockstring\Formatter;
use uuf6429\PhpCsFixerBlockstring\InterpolationCodec\GeneratedTokenCodec;

$isWin = PHP_OS_FAMILY === 'Windows';

return (new PhpCsFixer\Config())
	->registerCustomFixers([new BlockStringFixer()])
	->setRiskyAllowed(true)
	->setRules([
		'Uuf6429/block_string' => [
			'formatters' => [

				// 1️⃣ SimpleLineFormatter
				// Normalizes indentation of any block not explicitly configured below
				new Formatter\SimpleLineFormatter(
					indentSize: 4,
					indentChar: "\t",
					interpolationCodec: new GeneratedTokenCodec(),
				),

				// 2️⃣ CliPipeFormatter
				// Formats SQL using a CLI tool installed locally
				'SQL' => new Formatter\CliPipeFormatter(
					versionValueOrCommand: [
						'cmd' => $isWin
							? 'wsl sqlformat --version'
							: 'sqlformat --version'
					],
					formatCommand: [
						'cmd' => $isWin
							? 'wsl sqlformat --reindent -'
							: 'sqlformat --reindent -'
					],
				),

				// 3️⃣ ChainFormatter
				// Combines two formatters:
				// 1. A custom formatter that sorts object keys.
				// 2. A docker-based formatter that runs the json through jq.
				'JSON' => new Formatter\ChainFormatter(
					new class extends Formatter\AbstractCodecFormatter {
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

						private function sortObjectKeysRecursively(mixed $value): mixed
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
						image: 'ghcr.io/jqlang/jq',
						interpolationCodec: new GeneratedTokenCodec('"__PHP_VAR_%d__"'),
					),
				),

			],
		],
	]);
