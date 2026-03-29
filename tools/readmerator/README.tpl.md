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

## 💡 Before You Start

<details>
<summary>How does the configuration look like?</summary>

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
<summary>What's the deal with formatter versions?</summary>

You might have noticed that the base formatter class requires having a version. In most cases, the version comes up
quite often. The reason is that by supplying an up-to-date version, PHP-CS-Fixer cache can be skipped - which is
important if the recently-updated external fixer is behaving differently - otherwise fixes become outdated because of
an outdated cache. Note that the actual value of the version does not matter.
</details>

<details>
<summary>What about variable interpolation in Heredoc?</summary>

They provide an interesting challenge, which this project solves with the concept of an [`InterpolationCodec`].
This concept essentially replaces interpolation 'segments' with tokens - ensuring that the content is valid during the
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
`__PHP_VAR_1__` in this specific case - which is still not valid(!) So instead, we configure it with a different token
pattern: `new GeneratedTokenCodec('"__PHP_VAR_%d__"')`. The double quotes ensure that the replaced token is valid JSON:

```php
echo <<<JSON
	{"users": "__PHP_VAR_1__"}
	JSON;
```

Given that, the formatter will do its job without problems and then the codec will transform that token back with the
original interpolation.
</details>

<details>
<summary>What about complex variable interpolation in Heredoc?</summary>

The `GeneratedTokenCodec` codec additionally allows handling interpolations on a case-by-case basis by providing a
callback that acts as a token generation factory. If this callback returns null instead of a string token, the default
functionality will be used instead.

Additionally, you can always build your own codec - you just need to implement [`CodecInterface`].
</details>

<details>
<summary>The 3d-party formatter complains that the string has bad syntax.</summary>

This is not at all unlikely - that's one reason why the interpolation codec concept came up - string interpolation often
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
{{EXAMPLE_CONFIG}}
```

</details>

<details>
<summary>2. And the following source code file <i>(note that whitespace has been substituted to highlight it better)</i>:</summary>

```php
{{EXAMPLE_INPUT}}
```

</details>

<details>
<summary>3. PHP-CS-Fixer will format it to <i>(whitespace also substituted)</i>:</summary>

```php
{{EXAMPLE_OUTPUT}}
```

</details>

## ⭐️ Formatters

{{FORMATTERS}}

[PHP-CS-Fixer]: https://github.com/PHP-CS-Fixer/PHP-CS-Fixer

[Heredoc]: https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc

[Nowdoc]: https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.nowdoc

[`GeneratedTokenCodec`]: ./src/InterpolationCodec/GeneratedTokenCodec.php

[`CodecInterface`]: ./src/InterpolationCodec/CodecInterface.php

[`AbstractCodecFormatter`]: ./src/Formatter/AbstractCodecFormatter.php

[`ChainFormatter`]: ./src/Formatter/ChainFormatter.php
