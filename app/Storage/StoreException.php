<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * Thrown when the data store cannot be read or written.
 *
 * Callers turn this into a visible degraded state rather than rendering an
 * empty catalogue that would look like a successful load of nothing.
 */
final class StoreException extends \RuntimeException
{
}
