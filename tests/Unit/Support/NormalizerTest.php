<?php

declare(strict_types=1);

use SingraWorks\BrFields\Support\Normalizer;

mutates(Normalizer::class);

it('removes conventional punctuation and ascii whitespace', function (): void {
    expect(Normalizer::strip(" 12.345/678-9\t\n\r\v\f"))->toBe('123456789');
});

it('leaves every other character for the field to reject', function (): void {
    expect(Normalizer::strip("CPF: 1_2\u{00A0}3"))->toBe("CPF:1_2\u{00A0}3");
});
