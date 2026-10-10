<?php

declare(strict_types=1);

use Random\Engine;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use SingraWorks\BrFields\Documents\Cpf;
use SingraWorks\BrFields\Enums\FiscalRegion;
use SingraWorks\BrFields\Enums\Reason;
use SingraWorks\BrFields\Enums\Redaction;
use SingraWorks\BrFields\Exceptions\InvalidDocument;

mutates(Cpf::class);

// Vectors were computed by an independent implementation, not by Cpf itself.
dataset('valid cpfs', [
    '111.444.777-35' => ['111.444.777-35', '11144477735'],
    '529.982.247-25' => ['529.982.247-25', '52998224725'],
    'leading zero, second remainder below 2' => ['012.345.678-90', '01234567890'],
    'second remainder exactly 2' => ['100.000.000-19', '10000000019'],
]);

it('accepts a valid cpf', function (string $input, string $digits): void {
    $cpf = Cpf::from($input);

    expect($cpf->value())->toBe($digits)
        ->and(Cpf::isValid($input))->toBeTrue()
        ->and(Cpf::tryFrom($input))->toEqual($cpf);
})->with('valid cpfs');

it('strips conventional punctuation and whitespace', function (string $input): void {
    expect(Cpf::from($input)->value())->toBe('11144477735');
})->with([
    'bare' => '11144477735',
    'masked' => '111.444.777-35',
    'spaced' => '111 444 777 35',
    'padded' => "  111.444.777-35\n",
    'tabbed' => "111\t444\t777\t35",
]);

it('rejects a value with the wrong check digits', function (): void {
    expect(fn (): Cpf => Cpf::from('111.444.777-00'))
        ->toThrow(InvalidDocument::class, 'Invalid CPF: check digits do not match the rest of the document.');
});

it('rejects each check digit independently', function (string $input): void {
    expect(Cpf::isValid($input))->toBeFalse();
})->with([
    'first wrong' => '11144477745',
    'second wrong' => '11144477736',
]);

it('rejects every uniform sequence, though each one passes mod-11', function (string $digit): void {
    $input = str_repeat($digit, 11);

    try {
        Cpf::from($input);
        $this->fail('Expected InvalidDocument');
    } catch (InvalidDocument $invalidDocument) {
        expect($invalidDocument->reason)->toBe(Reason::RepeatedCharacters)
            ->and($invalidDocument->document)->toBe('cpf');
    }
})->with(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']);

it('reports the reason a value was rejected', function (string $input, Reason $reason, string $message): void {
    try {
        Cpf::from($input);
        $this->fail('Expected InvalidDocument');
    } catch (InvalidDocument $invalidDocument) {
        expect($invalidDocument->reason)->toBe($reason)
            ->and($invalidDocument->getMessage())->toBe($message);
    }
})->with([
    'too short' => ['1114447773', Reason::WrongLength, 'Invalid CPF: expected 11 characters, got 10.'],
    'too long' => ['111444777350', Reason::WrongLength, 'Invalid CPF: expected 11 characters, got 12.'],
    'empty' => ['', Reason::WrongLength, 'Invalid CPF: expected 11 characters, got 0.'],
    'prose' => ['CPF: 111.444.777-35', Reason::IllegalCharacter, 'Invalid CPF: contains characters outside 0-9.'],
    'interleaved letters' => ['1a1b4c4d4e7f7g7h3i5', Reason::IllegalCharacter, 'Invalid CPF: contains characters outside 0-9.'],
    'non-breaking space' => ["111.444.777\u{00A0}35", Reason::IllegalCharacter, 'Invalid CPF: contains characters outside 0-9.'],
    'non-ascii digits' => ['١١١٤٤٤٧٧٧٣٥', Reason::IllegalCharacter, 'Invalid CPF: contains characters outside 0-9.'],
]);

it('never restores leading zeros lost to an integer column', function (): void {
    expect(Cpf::tryFrom('1234567890'))->toBeNull();
});

it('formats a valid cpf through its mask', function (): void {
    expect(Cpf::from('01234567890')->formatted())->toBe('012.345.678-90');
});

it('formats without verifying the check digits', function (): void {
    expect(Cpf::isValid('11144477700'))->toBeFalse()
        ->and(Cpf::format('11144477700'))->toBe('111.444.777-00');
});

it('still enforces shape when formatting', function (): void {
    expect(fn (): string => Cpf::format('1234'))->toThrow(InvalidDocument::class);
});

it('redacts with each strategy', function (Redaction $strategy, string $expected): void {
    expect(Cpf::from('111.444.777-35')->redacted($strategy))->toBe($expected)
        ->and(Cpf::redact('11144477735', $strategy))->toBe($expected);
})->with([
    'default' => [Redaction::Default, '***.444.777-**'],
    'head' => [Redaction::Head, '111.***.***-**'],
    'tail' => [Redaction::Tail, '***.***.777-**'],
    'full' => [Redaction::Full, '***.***.***-**'],
]);

it('redacts by default with the government portal convention', function (): void {
    expect(Cpf::from('111.444.777-35')->redacted())->toBe('***.444.777-**')
        ->and(Cpf::redact('11144477735'))->toBe('***.444.777-**');
});

it('redacts without verifying the check digits, but still enforces shape', function (): void {
    expect(Cpf::redact('11144477700'))->toBe('***.444.777-**')
        ->and(fn (): string => Cpf::redact('1234'))->toThrow(InvalidDocument::class);
});

it('serialises to the bare digits', function (): void {
    $cpf = Cpf::from('012.345.678-90');

    expect((string) $cpf)->toBe('01234567890')
        ->and(json_encode($cpf))->toBe('"01234567890"');
});

it('reads the fiscal region from the ninth digit', function (string $digits, FiscalRegion $region): void {
    expect(Cpf::from($digits)->fiscalRegion())->toBe($region);
})->with([
    'tenth' => ['12345678062', FiscalRegion::Tenth],
    'first' => ['12345678143', FiscalRegion::First],
    'second' => ['12345678224', FiscalRegion::Second],
    'third' => ['12345678305', FiscalRegion::Third],
    'fourth' => ['12345678496', FiscalRegion::Fourth],
    'fifth' => ['12345678577', FiscalRegion::Fifth],
    'sixth' => ['12345678658', FiscalRegion::Sixth],
    'seventh' => ['12345678739', FiscalRegion::Seventh],
    'eighth' => ['12345678810', FiscalRegion::Eighth],
    'ninth' => ['12345678909', FiscalRegion::Ninth],
]);

it('generates cpfs that validate', function (): void {
    $randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', 'cpf', true)));

    for ($i = 0; $i < 500; $i++) {
        expect(Cpf::isValid(Cpf::generate($randomizer)->value()))->toBeTrue();
    }
});

it('generates reproducibly from a seeded randomizer', function (): void {
    $seeded = static fn (): Randomizer => new Randomizer(new Xoshiro256StarStar(hash('sha256', 'seed', true)));

    expect(Cpf::generate($seeded())->value())->toBe(Cpf::generate($seeded())->value());
});

it('generates in the requested fiscal region, including the zero-backed tenth', function (FiscalRegion $region): void {
    $randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', $region->name, true)));

    for ($i = 0; $i < 50; $i++) {
        expect(Cpf::generate($randomizer, $region)->fiscalRegion())->toBe($region);
    }
})->with(FiscalRegion::cases());

it('rerolls a generated uniform sequence', function (): void {
    // Zeros first, so the first candidate in the tenth region is 000.000.000-00.
    $engine = new class implements Engine
    {
        private int $calls = 0;

        private readonly Xoshiro256StarStar $fallback;

        public function __construct()
        {
            $this->fallback = new Xoshiro256StarStar(hash('sha256', 'reroll', true));
        }

        public function generate(): string
        {
            return ++$this->calls <= 8 ? "\0\0\0\0\0\0\0\0" : $this->fallback->generate();
        }
    };

    $cpf = Cpf::generate(new Randomizer($engine), FiscalRegion::Tenth);

    expect(Cpf::isValid($cpf->value()))->toBeTrue()
        ->and($cpf->fiscalRegion())->toBe(FiscalRegion::Tenth);
});

it('generates with the default randomizer', function (): void {
    expect(Cpf::isValid(Cpf::generate()->value()))->toBeTrue();
});
