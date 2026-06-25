<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox;

final class PublishLog
{
    /** @var list<string> */
    public array $ids = [];

    public function add(string $id): void
    {
        $this->ids[] = $id;
    }
}
