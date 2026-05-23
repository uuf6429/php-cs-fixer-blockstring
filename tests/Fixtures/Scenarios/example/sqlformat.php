<?php declare(strict_types=1);

// A fake php-based sql formatter

if (in_array('--version', $argv)) {
	die('1.0.23');
}

$content = $argv[$argc - 1] === '-' ? file_get_contents('php://stdin') : file_get_contents($argv[$argc - 1]);

echo trim(implode(
	"\n",
	array_map(
		'trim',
		explode(
			"\n",
			preg_replace_callback_array(
				[
					'/\b(select|insert|update|from|join|left|right|inner|outer|where|order by|group by|limit)\b/i'
					=> static function ($matches) {
						return "\n" . strtoupper($matches[0]);
					},
					'/\b(or|and|not|null|asc|desc)\b/i'
					=> static function ($matches) {
						return strtoupper($matches[0]);
					},
				],
				str_ireplace("\n", ' ', $content)
			)
		)
	)
));
