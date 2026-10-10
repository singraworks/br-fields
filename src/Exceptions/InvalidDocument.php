<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Exceptions;

use InvalidArgumentException;
use SingraWorks\BrFields\Enums\Reason;

/**
 * Thrown when a string cannot be accepted as the document it was offered as.
 *
 * The message is English and intended for logs. Consumers rendering a message
 * to a person should switch on {@see self::$reason} instead.
 */
final class InvalidDocument extends InvalidArgumentException implements BrFieldsException
{
    /**
     * @param string $document short identifier of the document type, e.g. "cpf"
     */
    public function __construct(
        public readonly string $document,
        public readonly Reason $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function wrongLength(string $document, int $expected, int $actual): self
    {
        return new self($document, Reason::WrongLength, sprintf(
            'Invalid %s: expected %d characters, got %d.',
            mb_strtoupper($document),
            $expected,
            $actual,
        ));
    }

    /**
     * @param string $detail phrase completing "Invalid CPF: ...", e.g.
     *                       "contains characters outside 0-9"
     */
    public static function illegalCharacter(string $document, string $detail): self
    {
        return new self($document, Reason::IllegalCharacter, sprintf(
            'Invalid %s: %s.',
            mb_strtoupper($document),
            $detail,
        ));
    }

    public static function repeatedCharacters(string $document): self
    {
        return new self($document, Reason::RepeatedCharacters, sprintf(
            'Invalid %s: every character is the same.',
            mb_strtoupper($document),
        ));
    }

    public static function invalidCheckDigit(string $document): self
    {
        return new self($document, Reason::InvalidCheckDigit, sprintf(
            'Invalid %s: check digits do not match the rest of the document.',
            mb_strtoupper($document),
        ));
    }
}
