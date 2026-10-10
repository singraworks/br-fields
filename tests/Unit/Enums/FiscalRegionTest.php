<?php

declare(strict_types=1);

use SingraWorks\BrFields\Enums\FiscalRegion;
use SingraWorks\BrFields\Enums\Uf;

mutates(FiscalRegion::class);

it('numbers the regions one through ten', function (): void {
    expect(array_map(fn (FiscalRegion $region): int => $region->number(), FiscalRegion::cases()))
        ->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
});

it('assigns every state to exactly one region', function (): void {
    $states = array_merge(...array_map(fn (FiscalRegion $region): array => $region->states(), FiscalRegion::cases()));
    $names = array_map(fn (Uf $uf): string => $uf->value, $states);
    sort($names);

    expect($names)->toBe(array_map(fn (Uf $uf): string => $uf->value, Uf::cases()));
});

it('maps each region to the states of the Receita Federal leaflet', function (FiscalRegion $region, array $states): void {
    expect($region->states())->toBe($states);
})->with([
    [FiscalRegion::First, [Uf::DF, Uf::GO, Uf::MS, Uf::MT, Uf::TO]],
    [FiscalRegion::Second, [Uf::AC, Uf::AM, Uf::AP, Uf::PA, Uf::RO, Uf::RR]],
    [FiscalRegion::Third, [Uf::CE, Uf::MA, Uf::PI]],
    [FiscalRegion::Fourth, [Uf::AL, Uf::PB, Uf::PE, Uf::RN]],
    [FiscalRegion::Fifth, [Uf::BA, Uf::SE]],
    [FiscalRegion::Sixth, [Uf::MG]],
    [FiscalRegion::Seventh, [Uf::ES, Uf::RJ]],
    [FiscalRegion::Eighth, [Uf::SP]],
    [FiscalRegion::Ninth, [Uf::PR, Uf::SC]],
    [FiscalRegion::Tenth, [Uf::RS]],
]);
