<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Documents;

use JsonSerializable;
use Random\Randomizer;
use SingraWorks\BrFields\Enums\FiscalRegion;
use SingraWorks\BrFields\Enums\Redaction;
use SingraWorks\BrFields\Exceptions\InvalidDocument;
use SingraWorks\BrFields\Support\Mask;
use SingraWorks\BrFields\Support\Normalizer;
use Stringable;

/**
 * Cadastro de Pessoas Físicas, the Brazilian individual taxpayer registry.
 *
 * Eleven digits: a nine-digit base followed by two mod-11 check digits. The
 * ninth digit of the base identifies the Receita Federal fiscal region that
 * issued it; see {@see self::fiscalRegion()}.
 *
 * An instance is proof of validity. Constructing one is the only way to obtain
 * it, and every constructor verifies the check digits.
 */
final readonly class Cpf implements JsonSerializable, Stringable
{
    private const string DOCUMENT = 'cpf';

    private const string ALPHABET = '0123456789';

    private const int LENGTH = 11;

    private const int BASE_LENGTH = 9;

    private const string MASK = '###.###.###-##';

    private function __construct(private string $digits) {}

    public function __toString(): string
    {
        return $this->digits;
    }

    /**
     * @throws InvalidDocument
     */
    public static function from(string $value): self
    {
        $digits = self::shaped($value);

        // Every uniform sequence satisfies the mod-11 arithmetic (111.111.111-11
        // checks out perfectly), so this rule is not redundant with the one below.
        if (self::isUniform($digits)) {
            throw InvalidDocument::repeatedCharacters(self::DOCUMENT);
        }

        if (mb_substr($digits, self::BASE_LENGTH) !== self::checkDigitsFor(mb_substr($digits, 0, self::BASE_LENGTH))) {
            throw InvalidDocument::invalidCheckDigit(self::DOCUMENT);
        }

        return new self($digits);
    }

    public static function tryFrom(string $value): ?self
    {
        try {
            return self::from($value);
        } catch (InvalidDocument) {
            return null;
        }
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) instanceof self;
    }

    /**
     * A syntactically valid, randomly generated CPF.
     *
     * Pass a seeded Randomizer to make fixtures reproducible; the default uses
     * the engine backing random_int().
     */
    public static function generate(?Randomizer $randomizer = null, ?FiscalRegion $region = null): self
    {
        $randomizer ??= new Randomizer();

        do {
            $base = $randomizer->getBytesFromString(self::ALPHABET, self::BASE_LENGTH - 1);

            // Written out rather than with ?? because the tenth region is
            // backed by 0, and a falsy-coalescing shorthand would silently
            // reroll it into a random digit.
            $base .= $region instanceof FiscalRegion
                ? (string) $region->value
                : $randomizer->getBytesFromString(self::ALPHABET, 1);
            $digits = $base.self::checkDigitsFor($base);
        } while (self::isUniform($digits));

        return new self($digits);
    }

    /**
     * Format for display without verifying the check digits.
     *
     * For legacy rows that must still render. The length and alphabet are still
     * enforced, so this never emits a malformed value.
     *
     * @throws InvalidDocument
     */
    public static function format(string $value): string
    {
        return new Mask(self::MASK)->format(self::shaped($value));
    }

    /**
     * Redact for display without verifying the check digits.
     *
     * @throws InvalidDocument
     */
    public static function redact(string $value, Redaction $strategy = Redaction::Default): string
    {
        return new Mask(self::MASK)->redact(self::shaped($value), self::visibleGroups($strategy));
    }

    /** The eleven digits, unpunctuated. */
    public function value(): string
    {
        return $this->digits;
    }

    /** Rendered through the mask: 123.456.789-09 */
    public function formatted(): string
    {
        return new Mask(self::MASK)->format($this->digits);
    }

    /** Privacy-preserving display: ***.456.789-** by default. */
    public function redacted(Redaction $strategy = Redaction::Default): string
    {
        return new Mask(self::MASK)->redact($this->digits, self::visibleGroups($strategy));
    }

    /**
     * The fiscal region that issued this CPF.
     *
     * Note this is a group of states in most cases, and identifies the issuing
     * office rather than the holder's residence. There is deliberately no uf()
     * method, because it could not be truthful.
     */
    public function fiscalRegion(): FiscalRegion
    {
        return FiscalRegion::from((int) $this->digits[self::BASE_LENGTH - 1]);
    }

    public function jsonSerialize(): string
    {
        return $this->digits;
    }

    /**
     * Normalise and check shape only. Alphabet first, so that "CPF: 123..."
     * reports its letters rather than a length that would puzzle the reader.
     *
     * @throws InvalidDocument
     */
    private static function shaped(string $value): string
    {
        $digits = Normalizer::strip($value);

        if (strspn($digits, self::ALPHABET) !== mb_strlen($digits)) {
            throw InvalidDocument::illegalCharacter(self::DOCUMENT, 'contains characters outside 0-9');
        }

        if (mb_strlen($digits) !== self::LENGTH) {
            throw InvalidDocument::wrongLength(self::DOCUMENT, self::LENGTH, mb_strlen($digits));
        }

        return $digits;
    }

    /** Both check digits for a nine-digit base. */
    private static function checkDigitsFor(string $base): string
    {
        $first = self::checkDigit($base);
        $second = self::checkDigit($base.$first);

        return $first.$second;
    }

    /**
     * Mod-11 over the base, weighted from strlen($base) + 1 down to 2.
     */
    private static function checkDigit(string $base): string
    {
        $length = mb_strlen($base);
        $sum = 0;

        for ($position = 0; $position < $length; $position++) {
            $sum += (int) $base[$position] * ($length + 1 - $position);
        }

        $remainder = $sum % 11;

        return (string) ($remainder < 2 ? 0 : 11 - $remainder);
    }

    private static function isUniform(string $digits): bool
    {
        return strspn($digits, $digits[0]) === mb_strlen($digits);
    }

    /** @return list<int> */
    private static function visibleGroups(Redaction $strategy): array
    {
        return match ($strategy) {
            Redaction::Default => [1, 2],
            Redaction::Head => [0],
            Redaction::Tail => [2],
            Redaction::Full => [],
        };
    }
}
