<?php

declare(strict_types=1);

use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use SingraWorks\BrFields\Documents\Cnpj;
use SingraWorks\BrFields\Enums\Reason;
use SingraWorks\BrFields\Enums\Redaction;
use SingraWorks\BrFields\Exceptions\InvalidDocument;

mutates(Cnpj::class);

// Vectors were computed by an independent implementation, not by Cnpj itself.
dataset('valid cnpjs', [
    'numeric' => ['11.222.333/0001-81', '11222333000181'],
    'numeric branch' => ['11.222.333/0002-62', '11222333000262'],
    'receita worked example' => ['12.ABC.345/01DE-35', '12ABC34501DE35'],
    'first alphanumeric ever issued' => ['00.000.000/E08G-12', '00000000E08G12'],
    'receita faq, corrected' => ['AA.345.678/0003-86', 'AA345678000386'],
    'first remainder 0' => ['11.222.333/0005-05', '11222333000505'],
    'first remainder 1' => ['11.222.333/0014-04', '11222333001404'],
    'first remainder 2' => ['11.222.333/0006-96', '11222333000696'],
    'second remainder 0' => ['11.222.333/0019-00', '11222333001900'],
    'second remainder 1' => ['11.222.333/0018-20', '11222333001820'],
    'second remainder 2' => ['11.222.333/0009-39', '11222333000939'],
]);

it('accepts a valid cnpj', function (string $input, string $characters): void {
    $cnpj = Cnpj::from($input);

    expect($cnpj->value())->toBe($characters)
        ->and(Cnpj::isValid($input))->toBeTrue()
        ->and(Cnpj::tryFrom($input))->toEqual($cnpj);
})->with('valid cnpjs');

it('normalises lowercase letters to uppercase', function (): void {
    $cnpj = Cnpj::from('12.abc.345/01de-35');

    expect($cnpj->value())->toBe('12ABC34501DE35')
        ->and($cnpj->formatted())->toBe('12.ABC.345/01DE-35');
});

it('rejects non-ascii letters that uppercase into valid ones', function (string $input): void {
    try {
        Cnpj::from($input);
        $this->fail('Expected InvalidDocument');
    } catch (InvalidDocument $invalidDocument) {
        expect($invalidDocument->reason)->toBe(Reason::IllegalCharacter);
    }
})->with([
    // Each one would become a valid CNPJ after mb_strtoupper().
    'sharp s becomes SS' => ["\u{00DF}222333000106"],
    'dotless i becomes I' => ["\u{0131}I222333000114"],
    'ff ligature becomes FF' => ["\u{FB00}222333000165"],
]);

it('rejects the faq example that carries wrong check digits', function (): void {
    expect(Cnpj::isValid('AA.345.678/0003-29'))->toBeFalse();
});

it('rejects each check digit independently', function (string $input): void {
    expect(Cnpj::isValid($input))->toBeFalse();
})->with([
    'first wrong' => '11222333000191',
    'second wrong' => '11222333000182',
    'alphanumeric, first wrong' => '12ABC34501DE45',
    'alphanumeric, second wrong' => '12ABC34501DE36',
]);

it('rejects every uniform sequence', function (string $digit): void {
    try {
        Cnpj::from(str_repeat($digit, 14));
        $this->fail('Expected InvalidDocument');
    } catch (InvalidDocument $invalidDocument) {
        expect($invalidDocument->reason)->toBe(Reason::RepeatedCharacters)
            ->and($invalidDocument->document)->toBe('cnpj');
    }
})->with(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']);

it('reports the reason a value was rejected', function (string $input, Reason $reason, string $message): void {
    try {
        Cnpj::from($input);
        $this->fail('Expected InvalidDocument');
    } catch (InvalidDocument $invalidDocument) {
        expect($invalidDocument->reason)->toBe($reason)
            ->and($invalidDocument->getMessage())->toBe($message);
    }
})->with([
    'too short' => ['1122233300018', Reason::WrongLength, 'Invalid CNPJ: expected 14 characters, got 13.'],
    'too long' => ['112223330001810', Reason::WrongLength, 'Invalid CNPJ: expected 14 characters, got 15.'],
    'symbol' => ['11.222.333/0001_81', Reason::IllegalCharacter, 'Invalid CNPJ: contains characters outside 0-9 and A-Z.'],
    'accented letter' => ['12.ÁBC.345/01DE-35', Reason::IllegalCharacter, 'Invalid CNPJ: contains characters outside 0-9 and A-Z.'],
    'letter in check digits' => ['12ABC34501DE3A', Reason::IllegalCharacter, 'Invalid CNPJ: check digits must be numeric.'],
    'invalid check digit' => ['11222333000180', Reason::InvalidCheckDigit, 'Invalid CNPJ: check digits do not match the rest of the document.'],
]);

it('formats without verifying the check digits, but still enforces shape', function (): void {
    expect(Cnpj::format('11222333000100'))->toBe('11.222.333/0001-00')
        ->and(Cnpj::format('12abc34501de00'))->toBe('12.ABC.345/01DE-00')
        ->and(fn (): string => Cnpj::format('1234'))->toThrow(InvalidDocument::class);
});

it('redacts with each strategy', function (Redaction $strategy, string $expected): void {
    expect(Cnpj::from('11.222.333/0001-81')->redacted($strategy))->toBe($expected)
        ->and(Cnpj::redact('11222333000181', $strategy))->toBe($expected);
})->with([
    'default' => [Redaction::Default, '**.222.333/0001-**'],
    'head' => [Redaction::Head, '11.***.***/****-**'],
    'tail' => [Redaction::Tail, '**.***.***/0001-**'],
    'full' => [Redaction::Full, '**.***.***/****-**'],
]);

it('redacts by default keeping everything but the first group and the check digits', function (): void {
    expect(Cnpj::from('12.ABC.345/01DE-35')->redacted())->toBe('**.ABC.345/01DE-**')
        ->and(Cnpj::redact('12abc34501de00'))->toBe('**.ABC.345/01DE-**');
});

it('splits into root and branch', function (): void {
    $headquarters = Cnpj::from('11.222.333/0001-81');
    $branch = Cnpj::from('11.222.333/0002-62');

    expect($headquarters->root())->toBe('11222333')
        ->and($headquarters->branch())->toBe('0001')
        ->and($headquarters->isHeadquarters())->toBeTrue()
        ->and($branch->root())->toBe('11222333')
        ->and($branch->branch())->toBe('0002')
        ->and($branch->isHeadquarters())->toBeFalse();
});

it('tells numeric and alphanumeric cnpjs apart', function (string $input, bool $alphanumeric): void {
    expect(Cnpj::from($input)->isAlphanumeric())->toBe($alphanumeric);
})->with([
    'numeric' => ['11222333000181', false],
    'letters in root' => ['12ABC34501DE35', true],
    'letters only in branch' => ['00000000E08G12', true],
]);

it('serialises to the bare uppercase characters', function (): void {
    $cnpj = Cnpj::from('12.abc.345/01de-35');

    expect((string) $cnpj)->toBe('12ABC34501DE35')
        ->and(json_encode($cnpj))->toBe('"12ABC34501DE35"');
});

it('generates numeric head-office cnpjs by default', function (): void {
    $randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', 'cnpj', true)));

    for ($i = 0; $i < 500; $i++) {
        $cnpj = Cnpj::generate($randomizer);

        expect(Cnpj::isValid($cnpj->value()))->toBeTrue()
            ->and($cnpj->isAlphanumeric())->toBeFalse()
            ->and($cnpj->isHeadquarters())->toBeTrue();
    }
});

it('generates alphanumeric cnpjs with at least one letter', function (): void {
    $randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', 'alphanumeric', true)));

    for ($i = 0; $i < 500; $i++) {
        $cnpj = Cnpj::generate($randomizer, alphanumeric: true);

        expect(Cnpj::isValid($cnpj->value()))->toBeTrue()
            ->and($cnpj->isAlphanumeric())->toBeTrue()
            ->and($cnpj->root())->toMatch('/[A-Z]/');
    }
});

it('generates reproducibly from a seeded randomizer', function (): void {
    $seeded = static fn (): Randomizer => new Randomizer(new Xoshiro256StarStar(hash('sha256', 'seed', true)));

    expect(Cnpj::generate($seeded(), alphanumeric: true)->value())
        ->toBe(Cnpj::generate($seeded(), alphanumeric: true)->value());
});

it('generates with the default randomizer', function (): void {
    expect(Cnpj::isValid(Cnpj::generate()->value()))->toBeTrue();
});
