<?php

declare(strict_types=1);

namespace BEAR\Defer;

use Override;

/**
 * Sequential, in-process deferred execution
 *
 * Bound as a singleton and shared between the interceptor (add) and the
 * transfer decorator (flush). flush() clears the queue first so the same
 * instance is safe to reuse for the next request on a long-running worker.
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
        foreach ($queue as $request) {
            $request();
        }
    }
}
