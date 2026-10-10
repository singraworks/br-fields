<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Enums;

/**
 * The Receita Federal fiscal region encoded in the ninth digit of a CPF.
 *
 * Two cautions, both of which the API deliberately makes hard to get wrong:
 *
 * 1. A region covers a GROUP of states, not one. Only the sixth (MG), eighth
 *    (SP) and tenth (RS) regions map to a single state.
 * 2. It identifies the office that ISSUED the CPF, not where the holder lives
 *    or was born. A person who registered in Recife and moved to Curitiba
 *    still carries a fourth-region CPF.
 *
 * This is why there is no Cpf::uf() — it would be a lie. The backing value is
 * the digit itself, so the tenth region is backed by 0, not 10.
 */
enum FiscalRegion: int
{
    case First = 1;
    case Second = 2;
    case Third = 3;
    case Fourth = 4;
    case Fifth = 5;
    case Sixth = 6;
    case Seventh = 7;
    case Eighth = 8;
    case Ninth = 9;
    case Tenth = 0;

    /** The region's ordinal number, 1 through 10. */
    public function number(): int
    {
        return $this === self::Tenth ? 10 : $this->value;
    }

    /**
     * The states served by this region.
     *
     * @return non-empty-list<Uf>
     */
    public function states(): array
    {
        return match ($this) {
            self::First => [Uf::DF, Uf::GO, Uf::MS, Uf::MT, Uf::TO],
            self::Second => [Uf::AC, Uf::AM, Uf::AP, Uf::PA, Uf::RO, Uf::RR],
            self::Third => [Uf::CE, Uf::MA, Uf::PI],
            self::Fourth => [Uf::AL, Uf::PB, Uf::PE, Uf::RN],
            self::Fifth => [Uf::BA, Uf::SE],
            self::Sixth => [Uf::MG],
            self::Seventh => [Uf::ES, Uf::RJ],
            self::Eighth => [Uf::SP],
            self::Ninth => [Uf::PR, Uf::SC],
            self::Tenth => [Uf::RS],
        };
    }
}
