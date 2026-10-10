<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Enums;

/**
 * How much of a value survives redaction.
 *
 * Every strategy hides the check digits. That is not decoration: check digits
 * are derived from the rest of the document, so leaving them visible collapses
 * the search space for the hidden characters. Showing ***.456.789-09 narrows
 * the hidden group from 1000 candidates to roughly 8.
 *
 * Which groups each strategy keeps is decided per document; see each document's
 * visibleGroups().
 */
enum Redaction
{
    /**
     * The document's own default, matching the convention used by Brazilian
     * government portals. For CPF: ***.456.789-**.
     */
    case Default;

    /** Only the first group survives. For CPF: 123.***.***-** */
    case Head;

    /** Only the last non-check-digit group survives. For CPF: ***.***.789-** */
    case Tail;

    /** Nothing survives. For CPF: ***.***.***-** */
    case Full;
}
