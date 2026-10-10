<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Enums;

/**
 * The 26 Brazilian states plus the Federal District.
 *
 * Labels are the official Portuguese names: they are proper nouns, not prose,
 * and translating them would produce names no Brazilian document ever uses.
 */
enum Uf: string
{
    case AC = 'AC';
    case AL = 'AL';
    case AM = 'AM';
    case AP = 'AP';
    case BA = 'BA';
    case CE = 'CE';
    case DF = 'DF';
    case ES = 'ES';
    case GO = 'GO';
    case MA = 'MA';
    case MG = 'MG';
    case MS = 'MS';
    case MT = 'MT';
    case PA = 'PA';
    case PB = 'PB';
    case PE = 'PE';
    case PI = 'PI';
    case PR = 'PR';
    case RJ = 'RJ';
    case RN = 'RN';
    case RO = 'RO';
    case RR = 'RR';
    case RS = 'RS';
    case SC = 'SC';
    case SE = 'SE';
    case SP = 'SP';
    case TO = 'TO';

    public function label(): string
    {
        return match ($this) {
            self::AC => 'Acre',
            self::AL => 'Alagoas',
            self::AM => 'Amazonas',
            self::AP => 'Amapá',
            self::BA => 'Bahia',
            self::CE => 'Ceará',
            self::DF => 'Distrito Federal',
            self::ES => 'Espírito Santo',
            self::GO => 'Goiás',
            self::MA => 'Maranhão',
            self::MG => 'Minas Gerais',
            self::MS => 'Mato Grosso do Sul',
            self::MT => 'Mato Grosso',
            self::PA => 'Pará',
            self::PB => 'Paraíba',
            self::PE => 'Pernambuco',
            self::PI => 'Piauí',
            self::PR => 'Paraná',
            self::RJ => 'Rio de Janeiro',
            self::RN => 'Rio Grande do Norte',
            self::RO => 'Rondônia',
            self::RR => 'Roraima',
            self::RS => 'Rio Grande do Sul',
            self::SC => 'Santa Catarina',
            self::SE => 'Sergipe',
            self::SP => 'São Paulo',
            self::TO => 'Tocantins',
        };
    }
}
