<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Support;

/**
 * A field's display pattern, and the two ways of rendering a value through it.
 *
 * '#' stands for one character of the value; every other character is a
 * literal separator:
 *
 *     (new Mask('###.###.###-##'))->format('12345678909')
 *     // 123.456.789-09
 *
 * Consecutive runs of '#' form groups numbered from zero. Redaction keeps the
 * groups it is given and stars the rest:
 *
 *     (new Mask('###.###.###-##'))->redact('12345678909', [1, 2])
 *     // ***.456.789-**
 *
 * Callers must pass a value whose length matches the pattern. Every field
 * checks its alphabet and length before reaching here, so this class does no
 * validating of its own.
 */
final readonly class Mask
{
    private const string PLACEHOLDER = '#';

    public function __construct(public string $pattern) {}

    public function format(string $value): string
    {
        return $this->render($value, null);
    }

    /**
     * @param list<int> $visibleGroups zero-based indices of groups to keep
     */
    public function redact(string $value, array $visibleGroups): string
    {
        return $this->render($value, $visibleGroups);
    }

    /**
     * @param null|list<int> $visibleGroups null keeps every group
     */
    private function render(string $value, ?array $visibleGroups): string
    {
        $rendered = '';
        $offset = 0;
        $group = 0;
        $afterSeparator = false;

        foreach (mb_str_split($this->pattern) as $character) {
            if ($character !== self::PLACEHOLDER) {
                $rendered .= $character;
                $afterSeparator = true;

                continue;
            }

            if ($afterSeparator) {
                $group++;
                $afterSeparator = false;
            }

            $visible = $visibleGroups === null || in_array($group, $visibleGroups, true);
            $rendered .= $visible ? $value[$offset] : '*';
            $offset++;
        }

        return $rendered;
    }
}
