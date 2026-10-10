<?php

declare(strict_types=1);

use SingraWorks\BrFields\Support\Mask;

mutates(Mask::class);

it('formats a value through the pattern', function (): void {
    expect(new Mask('###.###.###-##')->format('12345678909'))->toBe('123.456.789-09');
});

it('keeps only the requested groups when redacting', function (): void {
    $mask = new Mask('###.###.###-##');

    expect($mask->redact('12345678909', [1, 2]))->toBe('***.456.789-**')
        ->and($mask->redact('12345678909', [0, 3]))->toBe('123.***.***-09')
        ->and($mask->redact('12345678909', []))->toBe('***.***.***-**');
});

it('treats consecutive separators as a single group boundary', function (): void {
    expect(new Mask('##) (##')->redact('1234', [1]))->toBe('**) (34');
});
