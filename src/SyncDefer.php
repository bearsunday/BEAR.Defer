<?php

declare(strict_types=1);

namespace BEAR\Defer;

use BEAR\Defer\Exception\DeferFlushException;
use Override;
use Throwable;

/**
 * Sequential, in-process deferred execution
 *
 * Bound as a singleton and shared between the interceptor (add) and the
 * transfer decorator (flush). flush() clears the queue first so the same
 * instance is safe to reuse for the next request on a long-running worker,
 * and runs every request even if some throw so one failure does not drop
 * the rest; the collected failures are then raised as DeferFlushException.
 */
final class SyncDefer implements DeferInterface
{
    /** @var list<callable(): mixed> */
    private array $queue = [];

    #[Override]
    public function add(callable $request): void
    {
        $this->queue[] = $request;
    }

    #[Override]
    public function flush(): void
    {
        $queue = $this->queue;
        $this->queue = [];
        $errors = [];
        foreach ($queue as $request) {
            try {
                $request();
            } catch (Throwable $e) {
                $errors[] = $e;
            }
        }

        if ($errors !== []) {
            throw new DeferFlushException($errors);
        }
    }
}
