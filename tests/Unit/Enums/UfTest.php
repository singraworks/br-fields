<?php

declare(strict_types=1);

use SingraWorks\BrFields\Enums\Uf;

mutates(Uf::class);

it('labels every state with its official name', function (Uf $uf, string $label): void {
    expect($uf->label())->toBe($label);
})->with([
    [Uf::AC, 'Acre'],
    [Uf::AL, 'Alagoas'],
    [Uf::AM, 'Amazonas'],
    [Uf::AP, 'Amapá'],
    [Uf::BA, 'Bahia'],
    [Uf::CE, 'Ceará'],
    [Uf::DF, 'Distrito Federal'],
    [Uf::ES, 'Espírito Santo'],
    [Uf::GO, 'Goiás'],
    [Uf::MA, 'Maranhão'],
    [Uf::MG, 'Minas Gerais'],
    [Uf::MS, 'Mato Grosso do Sul'],
    [Uf::MT, 'Mato Grosso'],
    [Uf::PA, 'Pará'],
    [Uf::PB, 'Paraíba'],
    [Uf::PE, 'Pernambuco'],
    [Uf::PI, 'Piauí'],
    [Uf::PR, 'Paraná'],
    [Uf::RJ, 'Rio de Janeiro'],
    [Uf::RN, 'Rio Grande do Norte'],
    [Uf::RO, 'Rondônia'],
    [Uf::RR, 'Roraima'],
    [Uf::RS, 'Rio Grande do Sul'],
    [Uf::SC, 'Santa Catarina'],
    [Uf::SE, 'Sergipe'],
    [Uf::SP, 'São Paulo'],
    [Uf::TO, 'Tocantins'],
]);
