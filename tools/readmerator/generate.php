<?php

if (!isset($argv[1]) || !is_string($argv[1])) {
	throw new RuntimeException('README.md output target must be provided as cli argument');
}

$projectRoot = dirname(__DIR__, 2);
file_put_contents(
	$argv[1],
	strtr(
		file_get_contents(__DIR__ . '/README.tpl.md'),
		[
			'{{EXAMPLE_CONFIG}}' => file_get_contents("$projectRoot/tests/fixtures/example-config.php"),
			'{{EXAMPLE_INPUT}}' => str_replace(
				[' ', "\t"],
				['·', '---→'],
				file_get_contents("$projectRoot/tests/fixtures/example-input.php"),
			),
			'{{EXAMPLE_OUTPUT}}' => str_replace(
				[' ', "\t"],
				['·', '---→'],
				file_get_contents("$projectRoot/tests/fixtures/example-output.php")
			),
			'{{FORMATTERS}}' => rtrim(implode(
				"\n",
				array_map(
					static fn(string $classFile): string => sprintf(
						"### [%s](%s)\n\n%s\n",
						$className = basename($classFile, '.php'),
						str_replace($projectRoot, '.', $classFile),
						preg_match(
							"/\/\\*\\*(.*?)\\n(?: \\*\/| \* @)(.*?)\\n(abstract |final )?class $className/s",
							file_get_contents($classFile),
							$match
						) === 1
							? trim(preg_replace(['/\n \* ?/', '/\{@see ([^}]+)}/'], ["\n", '[`$1`]'], $match[1]))
							: ''
					),
					glob("$projectRoot/src/Formatter/*.php"),
				)
			), "\n"),
		]
	)
);

echo "README.md generated\n";
