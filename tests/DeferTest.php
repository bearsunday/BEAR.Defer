<?php

declare(strict_types=1);

namespace BEAR\Defer;

use PHPUnit\Framework\TestCase;

final class DeferTest extends TestCase
{
    protected Defer $defer;

    protected function setUp(): void
    {
        $this->defer = new Defer();
    }

    public function testIsInstanceOfDefer(): void
    {
        $actual = $this->defer;
        $this->assertInstanceOf(Defer::class, $actual);
    }
}
