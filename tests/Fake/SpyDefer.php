<?php

declare(strict_types=1);

namespace BEAR\Defer;

use Override;

final class SpyDefer implements DeferInterface
{
    /** @var list<callable(): mixed> */
    public array $added = [];

    public bool $flushed = false;

    #[Override]
    public function add(callable $request): void
    {
        $this->added[] = $request;
    }

    #[Override]
    public function flush(): void
    {
        $this->flushed = true;
    }
}
