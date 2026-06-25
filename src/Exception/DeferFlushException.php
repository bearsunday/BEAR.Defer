<?php

declare(strict_types=1);

namespace BEAR\Defer\Exception;

use Throwable;

use function count;
use function sprintf;

/**
 * Aggregates failures raised while flushing deferred requests
 *
 * flush() runs every request even if some throw, then raises this so that a
 * single failed deferred request does not silently drop the others.
 */
final class DeferFlushException extends RuntimeException
{
    /** @param non-empty-list<Throwable> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(
            sprintf('%d deferred request(s) failed during flush.', count($errors)),
            previous: $errors[0],
        );
    }
}
