# br-fields

[![CI](https://github.com/singraworks/br-fields/actions/workflows/ci.yml/badge.svg)](https://github.com/singraworks/br-fields/actions/workflows/ci.yml)
[![Downloads](https://img.shields.io/packagist/dt/singraworks/br-fields)](https://packagist.org/packages/singraworks/br-fields)

Validates, formats, and redacts Brazilian CPF and CNPJ numbers in plain PHP, with no Composer dependencies. CNPJs with letters, issued since July 2026, are supported.

## Install

```bash
composer require singraworks/br-fields
```

> It Requires **PHP 8.4+**

## Usage

```php
use SingraWorks\BrFields\Documents\Cnpj;
use SingraWorks\BrFields\Documents\Cpf;

Cpf::isValid('111.444.777-35');           // true
Cpf::tryFrom('111.444.777-00');           // null: wrong check digits

$cpf = Cpf::from('111.444.777-35');       // throws InvalidDocument if invalid
$cpf->value();                            // '11144477735'
$cpf->formatted();                        // '111.444.777-35'
$cpf->redacted();                         // '***.444.777-**'

$cnpj = Cnpj::from('12.abc.345/01de-35');
$cnpj->value();                           // '12ABC34501DE35'
$cnpj->formatted();                       // '12.ABC.345/01DE-35'
$cnpj->redacted();                        // '**.ABC.345/01DE-**'
```

Input may carry whitespace, `.`, `-`, and `/`. Any other character is rejected.

`redacted()` takes a `Redaction` strategy: `Default`, `Head`, `Tail`, or `Full`. Each one hides the check digits.

```php
use SingraWorks\BrFields\Enums\Redaction;

$cpf->redacted(Redaction::Head);          // '111.***.***-**'
```

To show a stored number whose check digits are wrong, use the static `Cpf::format()` and `Cpf::redact()`. They check length and characters only.

### Errors

`InvalidDocument` carries a `Reason` enum. Build the message people see from it, because the exception message is in English and meant for logs.

```php
use SingraWorks\BrFields\Enums\Reason;
use SingraWorks\BrFields\Exceptions\InvalidDocument;

try {
    $cpf = Cpf::from($input);
} catch (InvalidDocument $e) {
    $message = match ($e->reason) {
        Reason::WrongLength => 'O CPF deve ter 11 dígitos.',
        Reason::IllegalCharacter => 'O CPF deve conter apenas números.',
        Reason::RepeatedCharacters, Reason::InvalidCheckDigit => 'CPF inválido.',
    };
}
```

### Test fixtures

```php
Cpf::generate();                          // a valid random CPF
Cnpj::generate(alphanumeric: true);       // a valid random CNPJ with at least one letter
```

Pass a seeded `Random\Randomizer` as the first argument to get the same numbers on every run.

## Development

```bash
composer install
composer test           # the full gate: Rector and Pint dry runs, PHPStan, type coverage, code coverage
composer lint           # apply Rector and Pint fixes
composer analyse        # PHPStan only
composer test:mutate    # mutation testing
composer ci:check       # what CI runs
```

Thresholds are in [`composer.json`](composer.json). Coverage needs [pcov](https://github.com/krakjoe/pcov) or Xdebug.

## License

MIT. See [LICENSE](LICENSE). Maintained by [Singra](https://singraworks.com).
