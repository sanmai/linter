
[![Coverage Status](https://coveralls.io/repos/github/sanmai/linter/badge.svg?branch=master)](https://coveralls.io/github/sanmai/linter?branch=master)

# Simple PHP source code linter with zero dependencies

This is a very bare (really just 20 lines of code, comments included) yet very thoroughly tested (100% [MSI](https://medium.com/@maks_rafalko/infection-mutation-testing-framework-c9ccf02eefd1)) programmatic source code linter. Give it a piece of source code from a file or as a string, and it will tell you if this is a valid PHP or not. But do not forget to include the opening `<?php` tag.

The linter uses the default PHP executable found in `PATH`. If you want to test your code with a different, non-default, PHP executable, consider other libraries out there, careful `PATH` manipulation, or, provided you have a convincing argument, you can send a pull request.

## Install

	composer require sanmai/linter

## Use

```php
$linter = new \Linter\StringLinter('<?php return 1;');
$result = $linter->foundErrors();

var_dump($result);
// bool(false)
```

```php
$linter = new \Linter\StringLinter('<?php obviously invalid PHP code;');
$result = $linter->foundErrors();

var_dump($result);
// bool(true)
```

That's it!
