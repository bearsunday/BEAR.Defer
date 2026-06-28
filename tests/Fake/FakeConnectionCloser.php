<?php

declare(strict_types=1);

namespace BEAR\Defer;

use Override;

final class FakeConnectionCloser implements ConnectionCloserInterface
{
    public function __construct(private readonly CallLog $calls)
    {
    }

    #[Override]
    public function __invoke(): void
    {
        $this->calls->record('close');
    }
}
