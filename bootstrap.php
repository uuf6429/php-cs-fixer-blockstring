<?php declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
	if (strncmp($class, 'uuf6429\\PhpCsFixerBlockstring\\', 30) !== 0) {
		return;
	}

	require __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 30)) . '.php';
});
