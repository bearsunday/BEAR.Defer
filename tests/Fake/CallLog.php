<?php

declare(strict_types=1);

namespace BEAR\Defer;

final class CallLog
{
    /** @var list<string> */
    public array $calls = [];

    public function record(string $name): void
    {
        $this->calls[] = $name;
    }
}
