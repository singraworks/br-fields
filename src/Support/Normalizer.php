<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Support;

/**
 * Removes the punctuation Brazilian documents are conventionally written with.
 *
 * The allowlist is deliberate. Stripping every non-digit instead would accept
 * "CPF: 123.456.789-09" and "1a2b3c4d5e6f7g8h9i09" as valid CPFs, which would
 * make a Cpf instance prove nothing about the string it came from.
 *
 * Anything left behind that the document's alphabet does not permit (letters,
 * symbols, non-breaking spaces pasted out of a spreadsheet) is reported as
 * Reason::IllegalCharacter rather than silently discarded.
 */
final class Normalizer
{
    private const array STRIPPED = [' ', "\t", "\n", "\r", "\v", "\f", '.', '-', '/'];

    public static function strip(string $value): string
    {
        return str_replace(self::STRIPPED, '', $value);
    }
}
