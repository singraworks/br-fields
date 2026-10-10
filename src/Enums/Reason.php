<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Enums;

/**
 * Why a value was rejected.
 *
 * Messages carried by InvalidDocument are English, for logs. This enum is the
 * machine-readable contract consumers switch on to render their own messages,
 * localised however their application needs.
 */
enum Reason
{
    /** The value does not have the required number of characters. */
    case WrongLength;

    /** A character outside the document's alphabet survived normalisation. */
    case IllegalCharacter;

    /** Every character is the same, e.g. 111.111.111-11. */
    case RepeatedCharacters;

    /** The check digits do not match the rest of the document. */
    case InvalidCheckDigit;
}
