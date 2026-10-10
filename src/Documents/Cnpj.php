<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Documents;

use JsonSerializable;
use Random\Randomizer;
use SingraWorks\BrFields\Enums\Redaction;
use SingraWorks\BrFields\Exceptions\InvalidDocument;
use SingraWorks\BrFields\Support\Mask;
use SingraWorks\BrFields\Support\Normalizer;
use Stringable;

/**
 * Cadastro Nacional da Pessoa Jurídica, the Brazilian company registry.
 *
 * Fourteen positions: an eight-character root, a four-character branch order,
 * and two numeric check digits.
 *
 * Since July 2026 the root and branch may contain letters as well as digits
 * (Instrução Normativa RFB nº 2.229/2024). The check digits remain numeric and
 * mod-11 is still the rule; what changed is that each position now contributes
 * its ASCII value minus 48, so '0'–'9' keep their face value and 'A'–'Z' count
 * as 17–42. For an all-numeric CNPJ this reduces exactly to the pre-2026
 * arithmetic, which is why one routine serves both formats.
 *
 * Every CNPJ issued before the change stays valid indefinitely. Consumers whose
 * infrastructure cannot store letters should check {@see self::isAlphanumeric()}
 * at their boundary rather than reaching for a different validator.
 */
final readonly class Cnpj implements JsonSerializable, Stringable
{
    private const string DOCUMENT = 'cnpj';

    private const string DIGITS = '0123456789';

    private const string LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const string LOWERCASE_LETTERS = 'abcdefghijklmnopqrstuvwxyz';

    private const string ALPHABET = self::DIGITS.self::LETTERS;

    private const int LENGTH = 14;

    private const int BASE_LENGTH = 12;

    private const int ROOT_LENGTH = 8;

    private const string HEADQUARTERS = '0001';

    private const string MASK = '##.###.###/####-##';

    private function __construct(private string $characters) {}

    public function __toString(): string
    {
        return $this->characters;
    }

    /**
     * @throws InvalidDocument
     */
    public static function from(string $value): self
    {
        $characters = self::shaped($value);

        // 00.000.000/0000-00 satisfies the arithmetic, as every uniform
        // sequence does. It is still not a CNPJ.
        if (self::isUniform($characters)) {
            throw InvalidDocument::repeatedCharacters(self::DOCUMENT);
        }

        $base = mb_substr($characters, 0, self::BASE_LENGTH);

        if (mb_substr($characters, self::BASE_LENGTH) !== self::checkDigitsFor($base)) {
            throw InvalidDocument::invalidCheckDigit(self::DOCUMENT);
        }

        return new self($characters);
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
     * A syntactically valid, randomly generated CNPJ for the head office.
     *
     * Numeric by default, because that is still what almost every system in
     * the country holds. Pass $alphanumeric to fixture the post-2026 format;
     * at least one letter is guaranteed, so the flag always means what it says.
     */
    public static function generate(?Randomizer $randomizer = null, bool $alphanumeric = false): self
    {
        $randomizer ??= new Randomizer();
        $root = $randomizer->getBytesFromString($alphanumeric ? self::ALPHABET : self::DIGITS, self::ROOT_LENGTH);

        if ($alphanumeric) {
            $root[$randomizer->getInt(0, self::ROOT_LENGTH - 1)] = $randomizer->getBytesFromString(self::LETTERS, 1);
        }

        // No uniformity guard is needed here, unlike CPF: the branch is always
        // '0001', so the result always contains both a '0' and a '1'.
        $base = $root.self::HEADQUARTERS;

        return new self($base.self::checkDigitsFor($base));
    }

    /**
     * Format for display without verifying the check digits.
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

    /** The fourteen characters, unpunctuated and uppercase. */
    public function value(): string
    {
        return $this->characters;
    }

    /** Rendered through the mask: 11.222.333/0001-81 */
    public function formatted(): string
    {
        return new Mask(self::MASK)->format($this->characters);
    }

    /** Privacy-preserving display: **.222.333/0001-** by default. */
    public function redacted(Redaction $strategy = Redaction::Default): string
    {
        return new Mask(self::MASK)->redact($this->characters, self::visibleGroups($strategy));
    }

    /** The eight-character root shared by every establishment of a company. */
    public function root(): string
    {
        return mb_substr($this->characters, 0, self::ROOT_LENGTH);
    }

    /** The four-character establishment order; '0001' is the head office. */
    public function branch(): string
    {
        return mb_substr($this->characters, self::ROOT_LENGTH, self::BASE_LENGTH - self::ROOT_LENGTH);
    }

    public function isHeadquarters(): bool
    {
        return $this->branch() === self::HEADQUARTERS;
    }

    /** Whether this CNPJ uses the post-2026 alphabet rather than digits alone. */
    public function isAlphanumeric(): bool
    {
        return strspn($this->characters, self::DIGITS) !== self::LENGTH;
    }

    public function jsonSerialize(): string
    {
        return $this->characters;
    }

    /**
     * @throws InvalidDocument
     */
    private static function shaped(string $value): string
    {
        $characters = Normalizer::strip($value);

        // Checked before uppercasing, because mb_strtoupper() folds some
        // non-ASCII letters into ASCII ones: 'ß' becomes 'SS' and 'ı' becomes
        // 'I', which would let them through as a valid CNPJ.
        if (strspn($characters, self::ALPHABET.self::LOWERCASE_LETTERS) !== mb_strlen($characters)) {
            throw InvalidDocument::illegalCharacter(self::DOCUMENT, 'contains characters outside 0-9 and A-Z');
        }

        $characters = mb_strtoupper($characters);

        if (mb_strlen($characters) !== self::LENGTH) {
            throw InvalidDocument::wrongLength(self::DOCUMENT, self::LENGTH, mb_strlen($characters));
        }

        $checkDigits = mb_substr($characters, self::BASE_LENGTH);

        if (strspn($checkDigits, self::DIGITS) !== mb_strlen($checkDigits)) {
            throw InvalidDocument::illegalCharacter(self::DOCUMENT, 'check digits must be numeric');
        }

        return $characters;
    }

    private static function checkDigitsFor(string $base): string
    {
        $first = self::checkDigit($base);
        $second = self::checkDigit($base.$first);

        return $first.$second;
    }

    /**
     * Mod-11 where each position contributes (ASCII value - 48), weighted from
     * the right by the repeating sequence 2,3,4,5,6,7,8,9 then 2 again.
     */
    private static function checkDigit(string $base): string
    {
        $length = mb_strlen($base);
        $sum = 0;

        for ($position = 0; $position < $length; $position++) {
            $weight = (($length - 1 - $position) % 8) + 2;
            $sum += (ord($base[$position]) - 48) * $weight;
        }

        $remainder = $sum % 11;

        return (string) ($remainder < 2 ? 0 : 11 - $remainder);
    }

    private static function isUniform(string $characters): bool
    {
        return strspn($characters, $characters[0]) === mb_strlen($characters);
    }

    /** @return list<int> */
    private static function visibleGroups(Redaction $strategy): array
    {
        return match ($strategy) {
            Redaction::Default => [1, 2, 3],
            Redaction::Head => [0],
            Redaction::Tail => [3],
            Redaction::Full => [],
        };
    }
}
