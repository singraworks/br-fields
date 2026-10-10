<?php

declare(strict_types=1);

namespace SingraWorks\BrFields\Exceptions;

use Throwable;

/**
 * Marker implemented by every exception this package throws, so consumers can
 * catch everything originating here without naming individual classes.
 */
interface BrFieldsException extends Throwable {}
