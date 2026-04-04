# 🧹 PHP-CS-Fixer Heredoc/Nowdoc Content Formatter

[![CI](https://github.com/uuf6429/php-cs-fixer-blockstring/actions/workflows/ci.yml/badge.svg)](https://github.com/uuf6429/php-cs-fixer-blockstring/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/uuf6429/php-cs-fixer-blockstring/branch/main/graph/badge.svg)](https://codecov.io/gh/uuf6429/php-cs-fixer-blockstring)
[![Minimum PHP Version](https://img.shields.io/badge/php-%5E7.4%20%7C%7C%20%5E8-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/uuf6429/php-cs-fixer-blockstring/license)](https://packagist.org/packages/uuf6429/php-cs-fixer-blockstring)
[![Latest Stable Version](https://poser.pugx.org/uuf6429/php-cs-fixer-blockstring/v)](https://packagist.org/packages/uuf6429/php-cs-fixer-blockstring)
[![Latest Unstable Version](https://poser.pugx.org/uuf6429/php-cs-fixer-blockstring/v/unstable)](https://packagist.org/packages/uuf6429/php-cs-fixer-blockstring)

This project extends [PHP-CS-Fixer] to be able to format the contents of PHP [Heredoc] and [Nowdoc] strings (aka
_Block Strings_).

Note that **no language-specific formatters** are provided by design - this project instead provides the capability to
integrate any type of formatters with minimal code (mainly in your PHP-CS-Fixer configuration file).

While this might sound like a weakness, it in fact makes it possible to integrate virtually any formatter for any
language.

## 🔌 Installation

Install via Composer:

```shell
composer require uuf6429/php-cs-fixer-blockstring --dev
```

Finally, register a custom fixer in your `.php-cs-fixer.php` config file (1️⃣) and then set up formatters (2️⃣):

```php
<?php

use PhpCsFixer;
use uuf6429\PhpCsFixerBlockstring\Fixer\BlockStringFixer;

return (new PhpCsFixer\Config())
    ->registerCustomFixers([new BlockStringFixer()])  // 👈 1️⃣
    ->setRules([
        BlockStringFixer::NAME => [
            'formatters' => [],                       // 👈 2️⃣
        ]
    ])
    ->setFinder(...);
```

> [!WARNING]
> If PHP CS Fixer is installed via [`php-cs-fixer/shim`](https://github.com/PHP-CS-Fixer/shim) package, you may have to
> require the bootstrap file:
> ```php
> require __DIR__ . '/vendor/uuf6429/php-cs-fixer-blockstring/bootstrap.php';
> ```

## 💡 Before You Start

<details>
<summary>1. How does the configuration look like?</summary>

The configuration is made up of a map of block string delimiters and formatter pairs. A default formatter can be
configured to run for any Block Strings that have other not been configured.

For example:

```php
	BlockStringFixer::NAME => [
		'formatters' => [
			new LineFormatter(),
			'JSON' => new JsonFormatter(),
		]
	]
```

In that (fictitious) example, `LineFormatter` is applied to all block strings except `<<<JSON` - that one will be
handled exclusively by the `JsonFormatter` one.
</details>

<details>
<summary>2. What's the deal with formatter versions?</summary>

You might have noticed that the base formatter class requires having a version. Most formatters require a way for
providing such a version. The reason is that by supplying an up-to-date version, the PHP-CS-Fixer cache can be
skipped - which is important if the recently-updated external fixer is behaving differently - otherwise fixes become
outdated because of an outdated cache. Note that the actual value of the version does not matter. Some formatters
might be able to figure out the version by themselves.
</details>

<details>
<summary>3. What about variable interpolation in Heredoc?</summary>

They provide an interesting challenge, which this project solves with the concept of an [`InterpolationCodec`].
It works by replacing interpolation 'segments' with tokens - ensuring that the content is valid during the
formatting stage - and then they're rolled back to the original value.
The codec can be configured for most of the formatters - you should probably apply such configuration diligently if
you plan on having Heredoc strings.

Here's an example illustration of the whole flow:

```php
echo <<<JSON
	{"users": $users}
	JSON;
```

That JSON cannot be formatted properly because `$users` is not valid syntax. The [`GeneratedTokenCodec`] codec can be
used; it will automatically replace the `$users` part with a token temporarily. By default, it will replace it with
`__PHP_VAR_1__` in this specific case - which, however, is still not valid(!) So instead, we configure it with a
different token pattern: `new GeneratedTokenCodec('"__PHP_VAR_%d__"')`. The double quotes ensure that the replaced
token is valid JSON:

```php
echo <<<JSON
	{"users": "__PHP_VAR_1__"}
	JSON;
```

Given that, the formatter will do its job without problems, and then the codec will transform that token back to the
original interpolation.
</details>

<details>
<summary>4. What about complex variable interpolation in Heredoc?</summary>

The `GeneratedTokenCodec` codec additionally allows handling interpolations on a case-by-case basis by providing a
callback that acts as a token generation factory. If this callback returns null instead of a string token, the default
functionality will be used instead.

Additionally, you can always build your own codec - you just need to implement [`CodecInterface`].
</details>

<details>
<summary>5. The 3d-party/external formatter complains that the string has bad syntax.</summary>

This is not at all unlikely - that's one reason why the interpolation codec concept exists - string interpolation often
causes broken syntax. Unfortunately, the codec concept won't help you if you're using some other sort of templating
system, such as replacing placeholders with `str_replace()`, `preg_replace()`, `strtr()` or `sprintf()` or similar.
You can, however, implement a "formatter" that replaces such placeholders temporarily during formatting and then
reverses them back, but since this seems like an uncommon usecase, there aren't any supporting implementations yet
(you're welcome to suggest it though).
</details>

## 🚀 Usage Example

<details>
<summary>1. Given the following PHP-CS-Fixer configuration:</summary>

```php
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

```

</details>

<details>
<summary>2. ...and the following source code file <i>(whitespace has been replaced for better display)</i>:</summary>

```php
<?php·declare(strict_types=1);

/**
·*·Demo:·fetch·users·from·DB·and·generate·a·JS·snippet·with·JSON·data
·*/

$sql·=·<<<'SQL'
SELECT·id,·name,·email·from·users
········WHERE·status·=·'active'
····ORDER·by·created_at·desc
SQL;

/**·@var·PDO·$pdo·*/
$stmt·=·$pdo->query($sql);
$users·=·$stmt->fetchAll(PDO::FETCH_ASSOC);
$jsonUsers·=·json_encode($users);

$json·=·<<<"JSON"
---⟶{·····"users":{$jsonUsers},
---⟶····"ascending":···false··}
---⟶JSON;

echo·<<<JS
(function(){·····
····const·userData={$json};
---⟶console.log("Active·users:",·userData.users);
})();·····
JS;

```

</details>

<details>
<summary>3. ...PHP-CS-Fixer will format everything, resulting in <i>(whitespace also substituted)</i>:</summary>

```php
<?php·declare(strict_types=1);

/**
·*·Demo:·fetch·users·from·DB·and·generate·a·JS·snippet·with·JSON·data
·*/

$sql·=·<<<'SQL'
SELECT·id,·name,·email
FROM·users
WHERE·status·=·'active'
ORDER·BY·created_at·DESC
SQL;

/**·@var·PDO·$pdo·*/
$stmt·=·$pdo->query($sql);
$users·=·$stmt->fetchAll(PDO::FETCH_ASSOC);
$jsonUsers·=·json_encode($users);

$json·=·<<<"JSON"
---⟶{
---⟶··"ascending":·false,
---⟶··"users":·{$jsonUsers}
---⟶}
---⟶JSON;

echo·<<<JS
(function(){
---⟶const·userData={$json};
---⟶console.log("Active·users:",·userData.users);
})();
JS;

```

</details>

## ⭐️ Formatters

### [AbstractCodecFormatter](./src/Formatter/AbstractCodecFormatter.php)

This formatter base class is aware of string interpolation - it passes content through a codec before and after
formatting (to properly handle string interpolation).

Additionally, it keeps an in-memory cache of formatted content to avoid unnecessary work within the same process.

It can be used to embed any kind of formatter, including (native) PHP-based ones.

Example with your own custom class:

```php
final class MyFormatter extends AbstractCodecFormatter
{
    protected function formatContent(string $original): string
    {
        return 'new content';
    }
}

['formatters' => [ new MyFormatter('1.0', new PlainStringCodec()) ]]
```

Example with an anonymous class:

```php
['formatters' => [
    new class ('1.0', new PlainStringCodec()) extends AbstractCodecFormatter
    {
        protected function formatContent(string $original): string
        {
            return 'new content';
        }
    }
]]
```

### [AbstractFormatter](./src/Formatter/AbstractFormatter.php)

This is the base class of all formatters. In most cases you don't really want to extend this class, since it does
not handle string interpolation at all - check out [`AbstractCodecFormatter`] instead.

Extending this class makes sense in two situations:

1. If your class is infrastructural, and you don't really need to handle string interpolation - just like
   [`ChainFormatter`]
2. Or if, for whatever reason, the [`CodecInterface`] concept does not work for you and you want to write
   something from scratch.

### [ChainFormatter](./src/Formatter/ChainFormatter.php)

This formatter allows multiple formatters to be applied sequentially - the output of each formatter becomes the
input of the next one.

Example:

 ```php
 ['formatters' => [ new ChainFormatter(
     new FirstFormatter(),
     new SecondFormatter(),
 ) ]]
 ```

### [CliPipeFormatter](./src/Formatter/CliPipeFormatter.php)

It's no secret that the best formatting tools are not directly available in PHP. This formatter off-loads formatting
to such external executables.

Example:

```php
['formatters' => [ new CliPipeFormatter(
    versionValueOrCommand: '1.0',               // Either a version as a string, or the command to get the version (as an array).
    formatCommand: ['cmd' => 'jfmt -'],         // An array defining the external command to do the formatting.
    interpolationCodec: new PlainStringCodec(), // A codec for handling interpolations; depends on the content being formatted.
    stripLastNewLine: true,                     // Remove last line from cli output - you might need this, depending on the platform/shell.
) ]]
```

The command definition (for version detection or formatting) is an array with the following structure:

- `cmd` - array/string - The command line e.g. `'jfmt --format'` or `['jfmt', '--format']`.
- `cwd` - (optional) string - The current working directory of the command.
- `env` - (optional) array of string keys and values - Environment variables to pass to the command.

### [DockerPipeFormatter](./src/Formatter/DockerPipeFormatter.php)

The minimal setup, stable repeatability, and a rich ecosystem makes Docker images an ideal source of formatting
tools. This formatter exists to take advantage of that.

Example:

```php
['formatters' => [ new DockerPipeFormatter(
    image: 'ghcr.io/jqlang/jq',                 // The docker image; might contain url, tag or even the digest.
    options: ['-e', 'SOME_ENV=value'],          // Optional docker arguments, such as for setting env vars.
    command: ['bin/tool', '--dry-run', '-'],    // The command to run within the container, including any arguments.
    pullMode: 'always',                         // How/when the image should be pulled: 'never', 'always' or 'missing'.
    interpolationCodec: new PlainStringCodec(), // A codec for handling interpolations; depends on the content being formatted.
    stripLastNewLine: true,                     // Remove last line from docker output - typically needed.
) ]]
```

### [SimpleLineFormatter](./src/Formatter/SimpleLineFormatter.php)

A formatter that normalizes indentation and removes any trailing whitespace at the end of lines.

Example:

```php
['formatters' => [ new SimpleLineFormatter(
    indentSize: 4,                              // The number of spaces defining one indentation level in your project.
    indentChar: "\t",                           // The actual character used for indentation (space or tab).
    interpolationCodec: new PlainStringCodec(), // A codec for handling interpolations; depends on the content being formatted.
) ]]
```

### [WslPipeFormatter](./src/Formatter/WslPipeFormatter.php)

A formatter making use of Windows Subsystem for Linux (WSL). Of course you will need to be running on Windows and WSL
needs to be enabled and set up. Configuration is otherwise almost identical to [`CliPipeFormatter`].

[PHP-CS-Fixer]: https://github.com/PHP-CS-Fixer/PHP-CS-Fixer

[Heredoc]: https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc

[Nowdoc]: https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.nowdoc

[`GeneratedTokenCodec`]: ./src/InterpolationCodec/GeneratedTokenCodec.php

[`CodecInterface`]: ./src/InterpolationCodec/CodecInterface.php

[`AbstractCodecFormatter`]: ./src/Formatter/AbstractCodecFormatter.php

[`ChainFormatter`]: ./src/Formatter/ChainFormatter.php

[`InterpolationCodec`]: ./src/InterpolationCodec

[`CliPipeFormatter`]: ./src/Formatter/CliPipeFormatter.php
