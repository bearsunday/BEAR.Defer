<?php

declare(strict_types=1);

namespace BEAR\Defer;

interface DeferInterface
{
    /**
     * Add a deferred request to be executed after the response is transferred
     *
     * @param callable(): mixed $request
     */
    public function add(callable $request): void;

    /**
     * Execute all deferred requests and clear the queue
     *
     * Idempotent: flushing an empty queue is a no-op.
     */
    public function flush(): void;
}
